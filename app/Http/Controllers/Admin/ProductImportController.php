<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\Request;
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
//
// Deliberately narrow by design: just Name / Search Tags / Tag / Description / Price-per-250g.
// Every row becomes a loose, weight-based product with a fixed 250g/500g/1kg portion spread
// (500g/1kg prices are derived automatically — 2x/4x the 250g price, same as the single-product
// form) and a placeholder photo (image is a NOT NULL column, and this flow deliberately doesn't
// ask for an Image URL — the admin adds real photos by hand afterward, see
// public/images/products/placeholder.png). Category is chosen ONCE per upload (not a per-row
// column) — every product in a given file goes into the one category selected on the Bulk
// Upload page, which also drives the "Category" note in the downloaded template's Read Me sheet.
class ProductImportController extends Controller
{
    // column letter => template header label — the single source of truth for the column layout
    // so the header row, the example rows, and the Read Me sheet stay in sync automatically
    private const HEADERS = [
        'A' => 'Name',
        'B' => 'Search Tags (comma-separated)',
        'C' => 'Tag',
        'D' => 'Description',
        'E' => 'Price per 250g (Rs)',
    ];

    // every bulk-imported product gets this fixed portion spread rather than asking the admin to
    // specify one per row — matches the sizes already offered everywhere else in the catalog
    private const DEFAULT_PORTIONS = [250, 500, 1000];

    private const PLACEHOLDER_IMAGE = 'images/products/placeholder.png';

    public function create()
    {
        return view('admin.products.import', [
            'categories' => Category::where('is_active', true)->orderBy('sort_order')->get(),
        ]);
    }

    // .xlsx with 2 worked examples and a "Read Me" sheet explaining every column. category_id is
    // optional here (just personalizes the Read Me's category note) — the category that actually
    // matters is the one submitted with the upload itself, see store().
    public function template(Request $request)
    {
        $category = Category::find($request->query('category_id'));

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Products');

        foreach (self::HEADERS as $col => $label) {
            $sheet->setCellValue("{$col}1", $label);
        }
        $sheet->getStyle('A1:E1')->getFont()->setBold(true)->getColor()->setRGB('FFFFFF');
        $sheet->getStyle('A1:E1')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('7A1622');
        $sheet->getRowDimension(1)->setRowHeight(20);

        $examples = [
            ['Assorted Mithai Platter', 'mithai, sweets, diwali, gift', 'Bestseller', 'A festive assortment of traditional Indian sweets, freshly prepared.', 320],
            ['Premium Namkeen Mix', 'namkeen, snacks, farsan, tea time', '', 'A crunchy, spiced mix perfect for tea-time.', 180],
        ];
        foreach ($examples as $i => $row) {
            $rowNum = $i + 2;
            foreach (array_values($row) as $j => $value) {
                $sheet->setCellValue(chr(ord('A') + $j)."{$rowNum}", $value);
            }
        }

        foreach (range('A', 'E') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $help = $spreadsheet->createSheet();
        $help->setTitle('Read Me');
        $lines = [
            ['Column', 'What to enter'],
            ['Name', 'Product name. Required.'],
            ['Search Tags', 'Optional. Comma-separated keywords customers might search for, e.g. "mithai, sweets, diwali". Up to 30.'],
            ['Tag', 'Optional small badge label shown on the product, e.g. "Bestseller".'],
            ['Description', 'Optional.'],
            ['Price per 250g (Rs)', 'Whole rupees — the price for 250g. The 500g and 1kg prices are worked out automatically (2x and 4x this price).'],
            ['Category', $category
                ? "Every product in this file will be added to: {$category->name} (chosen when this template was downloaded)."
                : 'Choose a category on the Bulk Upload page before uploading — every product in this file goes into that one category, not a column in this sheet.'],
            ['Photo', 'Every imported product starts with a placeholder photo — add the real one afterward from Admin → Products → edit that product.'],
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
        $filename = $category ? Str::slug($category->name).'-import-template.xlsx' : 'product-import-template.xlsx';

        return response()->streamDownload(function () use ($writer) {
            $writer->save('php://output');
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    // Returns one JSON response with a per-row result — there's no queue worker to poll a job
    // status from, so "progress" on the frontend is real upload-transfer progress (XHR) followed
    // by an indeterminate spinner for this synchronous processing phase, not per-row polling.
    public function store(Request $request)
    {
        $data = $request->validate([
            'file' => 'required|file|mimes:xlsx,xls|max:5120',
            'category_id' => 'required|integer|exists:categories,id',
        ]);
        $category = Category::findOrFail($data['category_id']);

        // a few dozen rows is normally quick, but this route is still the one place in the admin
        // that processes a whole spreadsheet in one request — extra runway over the default 30s
        set_time_limit(300);

        $spreadsheet = IOFactory::load($request->file('file')->getRealPath());
        $sheet = $spreadsheet->getSheetByName('Products') ?? $spreadsheet->getActiveSheet();
        $highestRow = $sheet->getHighestDataRow();

        $results = [];
        $created = 0;

        for ($row = 2; $row <= $highestRow; $row++) {
            $get = fn (string $col) => trim((string) $sheet->getCell("{$col}{$row}")->getFormattedValue());

            $name = $get('A');
            if ($name === '' && $get('D') === '' && $get('E') === '') {
                continue; // fully blank row (trailing rows in the sheet) — not an error, just skip
            }

            $error = $this->importRow($get, $category);
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
    private function importRow(callable $get, Category $category): ?string
    {
        $name = $get('A');
        if ($name === '') {
            return 'Name is required.';
        }

        $priceDigits = preg_replace('/[^\d.]/', '', $get('E'));
        if ($priceDigits === '' || (float) $priceDigits < 1) {
            return 'Price per 250g must be a number of at least ₹1.';
        }
        $price = (int) round((float) $priceDigits);

        $searchTags = array_slice(
            array_values(array_filter(array_map('trim', explode(',', $get('B'))))),
            0,
            30
        );

        $product = Product::create([
            'name' => $name,
            'category' => $category->name,
            'description' => $get('D') ?: null,
            'price' => $price,
            'type' => 'loose',
            'unit' => 'weight',
            'portions' => self::DEFAULT_PORTIONS,
            'weight' => implode('/', array_map(fn ($g) => Product::portionLabel($g, 'weight'), self::DEFAULT_PORTIONS)),
            'tag' => $get('C') ?: null,
            'search_tags' => $searchTags,
            'color' => '#c8962e',
            'sort_order' => 0,
            'is_bestseller' => false,
            'is_festival_special' => false,
            'image' => self::PLACEHOLDER_IMAGE,
        ]);
        $product->categories()->sync([$category->id]);

        return null;
    }
}
