<?php

// Preset color themes for the customer-facing website (Admin → Application Customization).
// Adding an 11th theme is exactly one new array entry here — nothing else in the app needs to
// change. 'vars' feeds the CSS custom properties tailwind.config.js's maroon/gold colors now
// resolve through (see resources/css/app.css's :root defaults and layouts/app.blade.php's
// per-request override block); 'swatch' is just the two colors shown on the picker card in
// Admin → Application Customization. cream/ivory (the light neutral canvas) deliberately stay
// constant across every theme — see resources/css/app.css — so only the brand primary/secondary
// tokens vary, matching how "surface" vs "brand" tokens are usually kept independent.
//
// Every ramp mirrors the exact lightness role maroon_gold's own ramp uses at each stop (50/100 =
// near-white tints for badges, 400 = hover-lighter, 500 = base brand tone, 600/700 = hover/active,
// 800/900 = near-black for dark header/footer backgrounds) — generated from maroon_gold's own
// measured HSL curve, rotated to each theme's hue, so contrast stays consistent with what's
// already proven to work across this site today.

return [

    // Quick-commerce grocery look: bright green primary + amber accent, on a cooler
    // near-white canvas instead of the warm cream every other theme shares — cream/ivory
    // are normally never themed (see the file-level comment below), but this preset opts
    // in via its own 'cream'/'ivory' vars entries so only this theme's canvas shifts;
    // the other 9 stay pixel-identical since they don't define those keys.
    'fresh_green' => [
        'label' => 'Fresh Green',
        'description' => 'Bright, clean, and fast — a modern grocery-app feel',
        'recommended' => true,
        'swatch' => ['primary' => '#17783c', 'secondary' => '#f59e0b'],
        'vars' => [
            // same hue/saturation held constant across every stop (like every other theme here),
            // only lightness varies — 400 was previously literal Tailwind green-400, which is far
            // too light to use as body/caption text (see text-maroon-400 usages sitewide); this
            // ramp mirrors emerald_gold's proven lightness curve so every stop stays legible.
            'maroon-50' => '242 253 246', 'maroon-100' => '216 248 228', 'maroon-400' => '26 137 68',
            'maroon-500' => '23 120 60', 'maroon-600' => '20 107 52', 'maroon-700' => '14 71 36',
            'maroon-800' => '11 61 29', 'maroon-900' => '9 47 23',
            'gold-50' => '255 251 235', 'gold-100' => '254 243 199', 'gold-300' => '252 211 77',
            'gold-400' => '251 191 36', 'gold-500' => '245 158 11', 'gold-600' => '217 119 6',
            'cream' => '248 250 249', 'ivory' => '255 255 255',
        ],
    ],

    'maroon_gold' => [
        'label' => 'Maroon + Gold',
        'description' => 'Premium, luxury, traditional sweets brand identity',
        'recommended' => false,
        'swatch' => ['primary' => '#7a1622', 'secondary' => '#c8962e'],
        'vars' => [
            'maroon-50' => '253 242 242', 'maroon-100' => '249 217 217', 'maroon-400' => '138 28 43',
            'maroon-500' => '122 22 34', 'maroon-600' => '107 20 32', 'maroon-700' => '74 14 23',
            'maroon-800' => '58 11 18', 'maroon-900' => '46 9 16',
            'gold-50' => '253 248 236', 'gold-100' => '248 236 201', 'gold-300' => '233 200 115',
            'gold-400' => '212 169 64', 'gold-500' => '200 150 46', 'gold-600' => '169 122 31',
        ],
    ],

    'emerald_gold' => [
        'label' => 'Emerald Green + Gold',
        'description' => 'Rich, fresh, celebratory — a lively alternative to the classic maroon',
        'recommended' => false,
        'swatch' => ['primary' => '#1b744b', 'secondary' => '#cb982b'],
        'vars' => [
            'maroon-50' => '243 252 248', 'maroon-100' => '219 247 234', 'maroon-400' => '31 134 86',
            'maroon-500' => '27 116 75', 'maroon-600' => '24 103 66', 'maroon-700' => '17 71 46',
            'maroon-800' => '13 56 36', 'maroon-900' => '10 45 29',
            'gold-50' => '251 247 238', 'gold-100' => '244 232 205', 'gold-300' => '227 193 121',
            'gold-400' => '214 166 62', 'gold-500' => '203 152 43', 'gold-600' => '165 124 35',
        ],
    ],

    'royal_blue_gold' => [
        'label' => 'Royal Blue + Gold',
        'description' => 'Regal and trustworthy, gold accents keep it warm rather than corporate',
        'recommended' => false,
        'swatch' => ['primary' => '#1b3674', 'secondary' => '#cb982b'],
        'vars' => [
            'maroon-50' => '243 246 252', 'maroon-100' => '219 228 247', 'maroon-400' => '31 62 134',
            'maroon-500' => '27 54 116', 'maroon-600' => '24 48 103', 'maroon-700' => '17 33 71',
            'maroon-800' => '13 26 56', 'maroon-900' => '10 21 45',
            'gold-50' => '251 247 238', 'gold-100' => '244 232 205', 'gold-300' => '227 193 121',
            'gold-400' => '214 166 62', 'gold-500' => '203 152 43', 'gold-600' => '165 124 35',
        ],
    ],

    'deep_purple_gold' => [
        'label' => 'Deep Purple + Gold',
        'description' => 'Distinctive and festive, leans into celebration/gifting occasions',
        'recommended' => false,
        'swatch' => ['primary' => '#52256a', 'secondary' => '#cb982b'],
        'vars' => [
            'maroon-50' => '249 244 251', 'maroon-100' => '236 223 244', 'maroon-400' => '95 43 123',
            'maroon-500' => '82 37 106', 'maroon-600' => '73 33 94', 'maroon-700' => '50 23 65',
            'maroon-800' => '39 18 51', 'maroon-900' => '32 14 41',
            'gold-50' => '251 247 238', 'gold-100' => '244 232 205', 'gold-300' => '227 193 121',
            'gold-400' => '214 166 62', 'gold-500' => '203 152 43', 'gold-600' => '165 124 35',
        ],
    ],

    'burgundy_cream' => [
        'label' => 'Burgundy + Cream',
        'description' => 'Softer and warmer than maroon, with a muted cream-tan accent instead of gold',
        'recommended' => false,
        'swatch' => ['primary' => '#741b3c', 'secondary' => '#af8947'],
        'vars' => [
            'maroon-50' => '252 243 246', 'maroon-100' => '247 219 229', 'maroon-400' => '134 31 69',
            'maroon-500' => '116 27 60', 'maroon-600' => '103 24 53', 'maroon-700' => '71 17 37',
            'maroon-800' => '56 13 29', 'maroon-900' => '45 10 23',
            'gold-50' => '249 246 240', 'gold-100' => '237 228 212', 'gold-300' => '208 183 140',
            'gold-400' => '187 151 89', 'gold-500' => '175 137 71', 'gold-600' => '142 111 58',
        ],
    ],

    'forest_ivory' => [
        'label' => 'Forest Green + Ivory',
        'description' => 'Earthy and calm, a soft ivory-beige accent for a natural, artisanal feel',
        'recommended' => false,
        'swatch' => ['primary' => '#256a3f', 'secondary' => '#a28854'],
        'vars' => [
            'maroon-50' => '244 251 247', 'maroon-100' => '223 244 230', 'maroon-400' => '43 123 72',
            'maroon-500' => '37 106 63', 'maroon-600' => '33 94 55', 'maroon-700' => '23 65 38',
            'maroon-800' => '18 51 30', 'maroon-900' => '14 41 24',
            'gold-50' => '248 246 241', 'gold-100' => '234 228 215', 'gold-300' => '200 183 148',
            'gold-400' => '175 150 101', 'gold-500' => '162 136 84', 'gold-600' => '132 111 68',
        ],
    ],

    'navy_silver' => [
        'label' => 'Navy Blue + Silver',
        'description' => 'Deep and confident, cool silver accents for a modern, premium look',
        'recommended' => false,
        'swatch' => ['primary' => '#23406d', 'secondary' => '#6f7987'],
        'vars' => [
            'maroon-50' => '244 247 251', 'maroon-100' => '222 231 244', 'maroon-400' => '40 74 126',
            'maroon-500' => '35 64 109', 'maroon-600' => '30 57 97', 'maroon-700' => '21 40 67',
            'maroon-800' => '17 31 52', 'maroon-900' => '13 25 42',
            'gold-50' => '243 244 246', 'gold-100' => '221 224 227', 'gold-300' => '166 173 182',
            'gold-400' => '126 136 150', 'gold-500' => '111 121 135', 'gold-600' => '90 99 110',
        ],
    ],

    'charcoal_gold' => [
        'label' => 'Charcoal Black + Gold',
        'description' => 'Sleek and high-contrast, a classic premium pairing',
        'recommended' => false,
        'swatch' => ['primary' => '#292826', 'secondary' => '#cb982b'],
        'vars' => [
            'maroon-50' => '245 245 244', 'maroon-100' => '223 222 221', 'maroon-400' => '55 54 52',
            'maroon-500' => '41 40 38', 'maroon-600' => '30 29 28', 'maroon-700' => '21 20 20',
            'maroon-800' => '14 14 14', 'maroon-900' => '8 8 7',
            'gold-50' => '251 247 238', 'gold-100' => '244 232 205', 'gold-300' => '227 193 121',
            'gold-400' => '214 166 62', 'gold-500' => '203 152 43', 'gold-600' => '165 124 35',
        ],
    ],

    'chocolate_beige' => [
        'label' => 'Chocolate Brown + Beige',
        'description' => 'Warm and inviting, evokes rich desserts and comfort',
        'recommended' => false,
        'swatch' => ['primary' => '#6a4325', 'secondary' => '#aa814c'],
        'vars' => [
            'maroon-50' => '251 247 244', 'maroon-100' => '244 232 223', 'maroon-400' => '123 78 43',
            'maroon-500' => '106 67 37', 'maroon-600' => '94 59 33', 'maroon-700' => '65 41 23',
            'maroon-800' => '51 32 18', 'maroon-900' => '41 26 14',
            'gold-50' => '249 245 241', 'gold-100' => '236 226 213', 'gold-300' => '205 178 143',
            'gold-400' => '182 144 93', 'gold-500' => '170 129 76', 'gold-600' => '138 105 62',
        ],
    ],

    'teal_white' => [
        'label' => 'Teal + White',
        'description' => 'Fresh and contemporary, a clean, airy alternative to the traditional palette',
        'recommended' => false,
        'swatch' => ['primary' => '#206f6d', 'secondary' => '#609694'],
        'vars' => [
            'maroon-50' => '244 252 251', 'maroon-100' => '221 245 244', 'maroon-400' => '37 128 125',
            'maroon-500' => '32 111 109', 'maroon-600' => '29 98 96', 'maroon-700' => '20 68 67',
            'maroon-800' => '15 53 52', 'maroon-900' => '12 43 42',
            'gold-50' => '242 247 247', 'gold-100' => '218 231 231', 'gold-300' => '156 192 191',
            'gold-400' => '112 164 162', 'gold-500' => '96 150 148', 'gold-600' => '78 122 120',
        ],
    ],

];
