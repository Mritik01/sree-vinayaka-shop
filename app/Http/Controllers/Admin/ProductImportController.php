<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Product;
use App\Support\ImageCompressor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

// Lets the admin add many products at once from a spreadsheet instead of the one-at-a-time
// Add Product form (ProductController::store). Deliberately synchronous (no queue — see
// QUEUE_CONNECTION=sync in .env, there's no worker process to dispatch to) since this shop's
// catalog is realistically dozens-to-low-hundreds of rows per file, not thousands; the JSON
// response IS the finished result, not a "job started" ack.
class ProductImportController extends Controller
{
    // column letter => template header label, also drives the example-row writer and the
    // "Read Me" sheet below — the single source of truth for the column layout so the three
    // stay in sync automatically instead of drifting apart under separate edits
    private const HEADERS = [
        'A' => 'Name',
        'B' => 'Category',
        'C' => 'Type (piece/loose)',
        'D' => 'Price (Rs)',
        'E' => 'Unit (weight/volume) - loose only',
        'F' => 'Portions in grams, comma-separated - loose only',
        'G' => 'Weight / Pack Size - piece only',
        'H' => 'Description',
        'I' => 'Discount Type (percentage/flat)',
        'J' => 'Discount Value',
        'K' => 'Tag',
        'L' => 'Color (hex)',
        'M' => 'Bestseller (Yes/No)',
        'N' => 'Festival Special (Yes/No)',
        'O' => 'Image URL',
    ];

    public function create()
    {
        return view('admin.products.import');
    }

    // .xlsx with 2 worked examples (one "piece" product, one "loose" one) using this shop's own
    // real product photos as the Image URL example — so re-uploading the template unmodified
    // actually succeeds end-to-end, rather than just showing placeholder text nobody tests.
    public function template()
    {
        $categoryNames = Category::where('is_active', true)->orderBy('sort_order')->pluck('name')->all();
        $cat1 = $categoryNames[0] ?? 'Beverages';
        $cat2 = $categoryNames[1] ?? $cat1;

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Products');

        foreach (self::HEADERS as $col => $label) {
            $sheet->setCellValue("{$col}1", $label);
        }
        $sheet->getStyle('A1:O1')->getFont()->setBold(true)->getColor()->setRGB('FFFFFF');
        $sheet->getStyle('A1:O1')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('7A1622');
        $sheet->getRowDimension(1)->setRowHeight(20);

        $examples = [
            ['Thums Up 2L', $cat1, 'piece', 95, '', '', '2 L bottle', 'Strong, fizzy cola — the 2 litre bottle for family get-togethers.', '', '', 'Bestseller', '#0b3d91', 'Yes', 'No', asset('images/products/thumbs-up-6LnyX3.webp')],
            ['Assorted Mithai Platter', $cat2, 'loose', 320, 'weight', '250,500,1000', '', 'A festive assortment of traditional Indian sweets, freshly prepared.', '', '', 'Festival Special', '#8a1f2d', 'No', 'Yes', asset('images/products/balushai-eCxylU.png')],
        ];
        foreach ($examples as $i => $row) {
            $rowNum = $i + 2;
            foreach (array_values($row) as $j => $value) {
                $sheet->setCellValue(chr(ord('A') + $j)."{$rowNum}", $value);
            }
        }

        foreach (range('A', 'O') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $help = $spreadsheet->createSheet();
        $help->setTitle('Read Me');
        $lines = [
            ['Column', 'What to enter'],
            ['Name', 'Product name. Required.'],
            ['Category', 'Must exactly match one of your existing category names (case-insensitive): '.implode(', ', $categoryNames)],
            ['Type', '"piece" for a fixed pack (soap, soda bottle), or "loose" for something sold by weight/volume (loose mithai, namkeen).'],
            ['Price (Rs)', 'Whole rupees. For a loose product this is the price per 250g / 250ml — other portions are worked out from it automatically.'],
            ['Unit', 'Loose products only: "weight" or "volume".'],
            ['Portions', 'Loose products only: comma-separated sizes in grams/ml, e.g. 250,500,1000. Allowed values: '.implode(', ', Product::PORTION_OPTIONS)],
            ['Weight / Pack Size', 'Piece products only, e.g. "500g", "2 L bottle", "1 pc".'],
            ['Description', 'Optional.'],
            ['Discount Type / Value', 'Optional. "percentage" (1-100) or "flat" (rupees off). Leave both blank for no discount.'],
            ['Tag', 'Optional small badge label shown on the product, e.g. "Bestseller".'],
            ['Color', 'Optional hex color like #c8962e, used as the product card accent. Defaults to a standard gold if left blank.'],
            ['Bestseller / Festival Special', '"Yes" or "No".'],
            ['Image URL', 'Required — a direct, publicly-accessible link to the product photo. It is downloaded and compressed automatically, same as uploading it by hand.'],
        ];
        foreach ($lines as $i => [$a, $b]) {
            $help->setCellValue('A'.($i + 1), $a);
            $help->setCellValue('B'.($i + 1), $b);
        }
        $help->getStyle('A1:B1')->getFont()->setBold(true);
        $help->getColumnDimension('A')->setWidth(26);
        $help->getColumnDimension('B')->setWidth(95);
        $help->getStyle('A1:B'.count($lines))->getAlignment()->setWrapText(true)->setVertical('top');

        $spreadsheet->setActiveSheetIndex(0);

        $writer = new Xlsx($spreadsheet);

        return response()->streamDownload(function () use ($writer) {
            $writer->save('php://output');
        }, 'product-import-template.xlsx', [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    // Returns one JSON response with a per-row result — there's no queue worker to poll a job
    // status from, so "progress" on the frontend is real upload-transfer progress (XHR) followed
    // by an indeterminate spinner for this synchronous processing phase, not per-row polling.
    public function store(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls|max:5120',
        ]);

        // a few dozen rows each doing a network image fetch can comfortably exceed the default
        // 30s — this route is the only place that needs the extra runway
        set_time_limit(300);

        $spreadsheet = IOFactory::load($request->file('file')->getRealPath());
        $sheet = $spreadsheet->getSheetByName('Products') ?? $spreadsheet->getActiveSheet();
        $highestRow = $sheet->getHighestDataRow();

        $categoriesByLowerName = Category::where('is_active', true)->get()->keyBy(fn ($c) => Str::lower($c->name));

        $results = [];
        $created = 0;

        for ($row = 2; $row <= $highestRow; $row++) {
            $get = fn (string $col) => trim((string) $sheet->getCell("{$col}{$row}")->getFormattedValue());

            $name = $get('A');
            if ($name === '' && $get('B') === '' && $get('O') === '') {
                continue; // fully blank row (trailing rows in the sheet) — not an error, just skip
            }

            $error = $this->importRow($get, $categoriesByLowerName);
            $results[] = [
                'row' => $row,
                'name' => $name ?: '(unnamed)',
                'status' => $error ? 'error' : 'created',
                'message' => $error,
            ];
            if (!$error) {
                $created++;
            }
        }

        return response()->json([
            'ok' => true,
            'created' => $created,
            'failed' => count($results) - $created,
            'results' => $results,
        ]);
    }

    // Returns an error message, or null on success (and the product now exists) — a plain
    // nullable-string return keeps the per-row control flow in store() a simple early-return
    // chain instead of throwing/catching for what's routine, expected row-level validation.
    private function importRow(callable $get, $categoriesByLowerName): ?string
    {
        $name = $get('A');
        if ($name === '') {
            return 'Name is required.';
        }

        $categoryName = $get('B');
        $category = $categoriesByLowerName[Str::lower($categoryName)] ?? null;
        if (!$category) {
            return $categoryName === ''
                ? 'Category is required.'
                : "Category \"{$categoryName}\" doesn't match any existing category name.";
        }

        $type = Str::lower($get('C'));
        if (!in_array($type, ['piece', 'loose'], true)) {
            return 'Type must be "piece" or "loose".';
        }

        $priceDigits = preg_replace('/[^\d.]/', '', $get('D'));
        if ($priceDigits === '' || (float) $priceDigits < 1) {
            return 'Price must be a number of at least ₹1.';
        }
        $price = (int) round((float) $priceDigits);

        $data = [
            'name' => $name,
            'category' => $category->name,
            'description' => $get('H') ?: null,
            'price' => $price,
            'type' => $type,
            'tag' => $get('K') ?: null,
            'color' => preg_match('/^#[0-9a-f]{6}$/i', $get('L')) ? $get('L') : '#c8962e',
            'sort_order' => 0,
            'is_bestseller' => Str::lower($get('M')) === 'yes',
            'is_festival_special' => Str::lower($get('N')) === 'yes',
        ];

        if ($type === 'loose') {
            $unit = Str::lower($get('E'));
            if (!in_array($unit, ['weight', 'volume'], true)) {
                return 'Unit must be "weight" or "volume" for a loose product.';
            }

            $portionsRaw = array_filter(array_map('trim', explode(',', $get('F'))));
            if (!$portionsRaw) {
                return 'Portions are required for a loose product, e.g. 250,500,1000.';
            }
            $portions = [];
            foreach ($portionsRaw as $p) {
                if (!ctype_digit($p) || !in_array((int) $p, Product::PORTION_OPTIONS, true)) {
                    return "\"{$p}\" isn't a valid portion size. Allowed: ".implode(', ', Product::PORTION_OPTIONS);
                }
                $portions[] = (int) $p;
            }
            sort($portions);
            $data['unit'] = $unit;
            $data['portions'] = $portions;
            $data['weight'] = implode('/', array_map(fn ($g) => Product::portionLabel($g, $unit), $portions));
        } else {
            $weight = $get('G');
            if ($weight === '') {
                return 'Weight / Pack Size is required for a piece product.';
            }
            $data['weight'] = $weight;
            $data['unit'] = 'weight';
            $data['portions'] = null;
        }

        $discountType = Str::lower($get('I'));
        $discountValueRaw = $get('J');
        if ($discountType !== '' || $discountValueRaw !== '') {
            if (!in_array($discountType, ['percentage', 'flat'], true)) {
                return 'Discount Type must be "percentage" or "flat".';
            }
            if ($discountValueRaw === '' || !ctype_digit($discountValueRaw) || (int) $discountValueRaw < 1) {
                return 'Discount Value must be a whole positive number.';
            }
            $discountValue = (int) $discountValueRaw;
            if ($discountType === 'percentage' && $discountValue > 100) {
                return 'Discount percentage cannot exceed 100.';
            }
            if ($discountType === 'flat' && $discountValue >= $price) {
                return 'Flat discount must be less than the price.';
            }
            $data['discount_type'] = $discountType;
            $data['discount_value'] = $discountValue;
        }

        $imageUrl = $get('O');
        if ($imageUrl === '') {
            return 'Image URL is required.';
        }
        $imagePath = $this->downloadAndStoreImage($imageUrl, $name);
        if (!$imagePath) {
            return 'Could not download that Image URL — check it is a direct, publicly accessible link to an image file.';
        }
        $data['image'] = $imagePath;

        $product = Product::create($data);
        $product->categories()->sync([$category->id]);

        return null;
    }

    // mirrors ProductController::compressAndStore() for a URL-sourced image instead of an
    // uploaded file — same directory, filename pattern, and compression pipeline, so a
    // bulk-imported product's photo is indistinguishable on disk from one added by hand
    private function downloadAndStoreImage(string $url, string $name): ?string
    {
        try {
            $response = Http::timeout(15)->get($url);
        } catch (\Throwable $e) {
            return null;
        }
        if (!$response->successful()) {
            return null;
        }

        $binary = $response->body();
        if (!@imagecreatefromstring($binary)) {
            return null; // fetched fine, but not a decodable image
        }

        $compressed = ImageCompressor::compressToJpeg($binary);
        $rawExtension = strtolower(pathinfo(parse_url($url, PHP_URL_PATH) ?? '', PATHINFO_EXTENSION));
        $extension = $compressed !== $binary
            ? 'jpg'
            : (in_array($rawExtension, ['jpg', 'jpeg', 'png', 'webp', 'gif'], true) ? $rawExtension : 'jpg');

        $filename = Str::slug($name).'-'.Str::random(6).'.'.$extension;
        $directory = public_path('images/products');
        if (!is_dir($directory)) {
            mkdir($directory, 0755, true);
        }
        file_put_contents($directory.'/'.$filename, $compressed);

        return 'images/products/'.$filename;
    }
}
