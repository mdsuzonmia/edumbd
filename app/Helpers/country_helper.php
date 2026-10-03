<?php

if (!function_exists('get_country_list')) {

    function get_country_list()
    {
        return [

            'AF' => ['name' => 'Afghanistan', 'phone_code' => '+93', 'currency_code' => 'AFN', 'currency_symbol' => '؋'],
            'AL' => ['name' => 'Albania', 'phone_code' => '+355', 'currency_code' => 'ALL', 'currency_symbol' => 'L'],
            'DZ' => ['name' => 'Algeria', 'phone_code' => '+213', 'currency_code' => 'DZD', 'currency_symbol' => 'دج'],
            'AD' => ['name' => 'Andorra', 'phone_code' => '+376', 'currency_code' => 'EUR', 'currency_symbol' => '€'],
            'AO' => ['name' => 'Angola', 'phone_code' => '+244', 'currency_code' => 'AOA', 'currency_symbol' => 'Kz'],
            'AR' => ['name' => 'Argentina', 'phone_code' => '+54', 'currency_code' => 'ARS', 'currency_symbol' => '$'],
            'AM' => ['name' => 'Armenia', 'phone_code' => '+374', 'currency_code' => 'AMD', 'currency_symbol' => '֏'],
            'AU' => ['name' => 'Australia', 'phone_code' => '+61', 'currency_code' => 'AUD', 'currency_symbol' => '$'],
            'AT' => ['name' => 'Austria', 'phone_code' => '+43', 'currency_code' => 'EUR', 'currency_symbol' => '€'],
            'AZ' => ['name' => 'Azerbaijan', 'phone_code' => '+994', 'currency_code' => 'AZN', 'currency_symbol' => '₼'],

            'BS' => ['name' => 'Bahamas', 'phone_code' => '+1', 'currency_code' => 'BSD', 'currency_symbol' => '$'],
            'BH' => ['name' => 'Bahrain', 'phone_code' => '+973', 'currency_code' => 'BHD', 'currency_symbol' => '.د.ب'],
            'BD' => ['name' => 'Bangladesh', 'phone_code' => '+880', 'currency_code' => 'BDT', 'currency_symbol' => '৳'],
            'BB' => ['name' => 'Barbados', 'phone_code' => '+1', 'currency_code' => 'BBD', 'currency_symbol' => '$'],
            'BY' => ['name' => 'Belarus', 'phone_code' => '+375', 'currency_code' => 'BYN', 'currency_symbol' => 'Br'],
            'BE' => ['name' => 'Belgium', 'phone_code' => '+32', 'currency_code' => 'EUR', 'currency_symbol' => '€'],
            'BZ' => ['name' => 'Belize', 'phone_code' => '+501', 'currency_code' => 'BZD', 'currency_symbol' => '$'],
            'BJ' => ['name' => 'Benin', 'phone_code' => '+229', 'currency_code' => 'XOF', 'currency_symbol' => 'CFA'],
            'BT' => ['name' => 'Bhutan', 'phone_code' => '+975', 'currency_code' => 'BTN', 'currency_symbol' => 'Nu'],
            'BO' => ['name' => 'Bolivia', 'phone_code' => '+591', 'currency_code' => 'BOB', 'currency_symbol' => 'Bs'],

            'BA' => ['name' => 'Bosnia and Herzegovina', 'phone_code' => '+387', 'currency_code' => 'BAM', 'currency_symbol' => 'KM'],
            'BW' => ['name' => 'Botswana', 'phone_code' => '+267', 'currency_code' => 'BWP', 'currency_symbol' => 'P'],
            'BR' => ['name' => 'Brazil', 'phone_code' => '+55', 'currency_code' => 'BRL', 'currency_symbol' => 'R$'],
            'BN' => ['name' => 'Brunei', 'phone_code' => '+673', 'currency_code' => 'BND', 'currency_symbol' => '$'],
            'BG' => ['name' => 'Bulgaria', 'phone_code' => '+359', 'currency_code' => 'BGN', 'currency_symbol' => 'лв'],
            'BF' => ['name' => 'Burkina Faso', 'phone_code' => '+226', 'currency_code' => 'XOF', 'currency_symbol' => 'CFA'],
            'BI' => ['name' => 'Burundi', 'phone_code' => '+257', 'currency_code' => 'BIF', 'currency_symbol' => 'FBu'],

            'KH' => ['name' => 'Cambodia', 'phone_code' => '+855', 'currency_code' => 'KHR', 'currency_symbol' => '៛'],
            'CM' => ['name' => 'Cameroon', 'phone_code' => '+237', 'currency_code' => 'XAF', 'currency_symbol' => 'CFA'],
            'CA' => ['name' => 'Canada', 'phone_code' => '+1', 'currency_code' => 'CAD', 'currency_symbol' => '$'],
            'CV' => ['name' => 'Cape Verde', 'phone_code' => '+238', 'currency_code' => 'CVE', 'currency_symbol' => '$'],
            'CF' => ['name' => 'Central African Republic', 'phone_code' => '+236', 'currency_code' => 'XAF', 'currency_symbol' => 'CFA'],
            'TD' => ['name' => 'Chad', 'phone_code' => '+235', 'currency_code' => 'XAF', 'currency_symbol' => 'CFA'],
            'CL' => ['name' => 'Chile', 'phone_code' => '+56', 'currency_code' => 'CLP', 'currency_symbol' => '$'],
            'CN' => ['name' => 'China', 'phone_code' => '+86', 'currency_code' => 'CNY', 'currency_symbol' => '¥'],
            'CO' => ['name' => 'Colombia', 'phone_code' => '+57', 'currency_code' => 'COP', 'currency_symbol' => '$'],
            'KM' => ['name' => 'Comoros', 'phone_code' => '+269', 'currency_code' => 'KMF', 'currency_symbol' => 'CF'],

            'CG' => ['name' => 'Congo', 'phone_code' => '+242', 'currency_code' => 'XAF', 'currency_symbol' => 'CFA'],
            'CD' => ['name' => 'Democratic Republic of the Congo', 'phone_code' => '+243', 'currency_code' => 'CDF', 'currency_symbol' => 'FC'],
            'CR' => ['name' => 'Costa Rica', 'phone_code' => '+506', 'currency_code' => 'CRC', 'currency_symbol' => '₡'],
            'HR' => ['name' => 'Croatia', 'phone_code' => '+385', 'currency_code' => 'EUR', 'currency_symbol' => '€'],
            'CU' => ['name' => 'Cuba', 'phone_code' => '+53', 'currency_code' => 'CUP', 'currency_symbol' => '$'],
            'CY' => ['name' => 'Cyprus', 'phone_code' => '+357', 'currency_code' => 'EUR', 'currency_symbol' => '€'],
            'CZ' => ['name' => 'Czech Republic', 'phone_code' => '+420', 'currency_code' => 'CZK', 'currency_symbol' => 'Kč'],

            'DK' => ['name' => 'Denmark', 'phone_code' => '+45', 'currency_code' => 'DKK', 'currency_symbol' => 'kr'],
            'DJ' => ['name' => 'Djibouti', 'phone_code' => '+253', 'currency_code' => 'DJF', 'currency_symbol' => 'Fdj'],
            'DM' => ['name' => 'Dominica', 'phone_code' => '+1', 'currency_code' => 'XCD', 'currency_symbol' => '$'],
            'DO' => ['name' => 'Dominican Republic', 'phone_code' => '+1', 'currency_code' => 'DOP', 'currency_symbol' => '$'],

            'EG' => ['name' => 'Egypt', 'phone_code' => '+20', 'currency_code' => 'EGP', 'currency_symbol' => '£'],
            'SV' => ['name' => 'El Salvador', 'phone_code' => '+503', 'currency_code' => 'USD', 'currency_symbol' => '$'],
            'EE' => ['name' => 'Estonia', 'phone_code' => '+372', 'currency_code' => 'EUR', 'currency_symbol' => '€'],
            'ET' => ['name' => 'Ethiopia', 'phone_code' => '+251', 'currency_code' => 'ETB', 'currency_symbol' => 'Br'],

            'FI' => ['name' => 'Finland', 'phone_code' => '+358', 'currency_code' => 'EUR', 'currency_symbol' => '€'],
            'FR' => ['name' => 'France', 'phone_code' => '+33', 'currency_code' => 'EUR', 'currency_symbol' => '€'],

            'DE' => ['name' => 'Germany', 'phone_code' => '+49', 'currency_code' => 'EUR', 'currency_symbol' => '€'],
            'GH' => ['name' => 'Ghana', 'phone_code' => '+233', 'currency_code' => 'GHS', 'currency_symbol' => '₵'],
            'GR' => ['name' => 'Greece', 'phone_code' => '+30', 'currency_code' => 'EUR', 'currency_symbol' => '€'],
            'GT' => ['name' => 'Guatemala', 'phone_code' => '+502', 'currency_code' => 'GTQ', 'currency_symbol' => 'Q'],

            'HN' => ['name' => 'Honduras', 'phone_code' => '+504', 'currency_code' => 'HNL', 'currency_symbol' => 'L'],
            'HK' => ['name' => 'Hong Kong', 'phone_code' => '+852', 'currency_code' => 'HKD', 'currency_symbol' => '$'],
            'HU' => ['name' => 'Hungary', 'phone_code' => '+36', 'currency_code' => 'HUF', 'currency_symbol' => 'Ft'],

            'IN' => ['name' => 'India', 'phone_code' => '+91', 'currency_code' => 'INR', 'currency_symbol' => '₹'],
            'ID' => ['name' => 'Indonesia', 'phone_code' => '+62', 'currency_code' => 'IDR', 'currency_symbol' => 'Rp'],
            'IR' => ['name' => 'Iran', 'phone_code' => '+98', 'currency_code' => 'IRR', 'currency_symbol' => '﷼'],
            'IQ' => ['name' => 'Iraq', 'phone_code' => '+964', 'currency_code' => 'IQD', 'currency_symbol' => 'ع.د'],
            'IE' => ['name' => 'Ireland', 'phone_code' => '+353', 'currency_code' => 'EUR', 'currency_symbol' => '€'],
            'IL' => ['name' => 'Israel', 'phone_code' => '+972', 'currency_code' => 'ILS', 'currency_symbol' => '₪'],
            'IT' => ['name' => 'Italy', 'phone_code' => '+39', 'currency_code' => 'EUR', 'currency_symbol' => '€'],

            'JP' => ['name' => 'Japan', 'phone_code' => '+81', 'currency_code' => 'JPY', 'currency_symbol' => '¥'],

            'KE' => ['name' => 'Kenya', 'phone_code' => '+254', 'currency_code' => 'KES', 'currency_symbol' => 'KSh'],

            'KW' => ['name' => 'Kuwait', 'phone_code' => '+965', 'currency_code' => 'KWD', 'currency_symbol' => 'KD'],

            'MY' => ['name' => 'Malaysia', 'phone_code' => '+60', 'currency_code' => 'MYR', 'currency_symbol' => 'RM'],
            'MX' => ['name' => 'Mexico', 'phone_code' => '+52', 'currency_code' => 'MXN', 'currency_symbol' => '$'],

            'NP' => ['name' => 'Nepal', 'phone_code' => '+977', 'currency_code' => 'NPR', 'currency_symbol' => '₨'],
            'NL' => ['name' => 'Netherlands', 'phone_code' => '+31', 'currency_code' => 'EUR', 'currency_symbol' => '€'],
            'NZ' => ['name' => 'New Zealand', 'phone_code' => '+64', 'currency_code' => 'NZD', 'currency_symbol' => '$'],
            'NG' => ['name' => 'Nigeria', 'phone_code' => '+234', 'currency_code' => 'NGN', 'currency_symbol' => '₦'],
            'NO' => ['name' => 'Norway', 'phone_code' => '+47', 'currency_code' => 'NOK', 'currency_symbol' => 'kr'],

            'PK' => ['name' => 'Pakistan', 'phone_code' => '+92', 'currency_code' => 'PKR', 'currency_symbol' => '₨'],

            'PH' => ['name' => 'Philippines', 'phone_code' => '+63', 'currency_code' => 'PHP', 'currency_symbol' => '₱'],
            'PL' => ['name' => 'Poland', 'phone_code' => '+48', 'currency_code' => 'PLN', 'currency_symbol' => 'zł'],
            'PT' => ['name' => 'Portugal', 'phone_code' => '+351', 'currency_code' => 'EUR', 'currency_symbol' => '€'],

            'QA' => ['name' => 'Qatar', 'phone_code' => '+974', 'currency_code' => 'QAR', 'currency_symbol' => '﷼'],

            'RO' => ['name' => 'Romania', 'phone_code' => '+40', 'currency_code' => 'RON', 'currency_symbol' => 'lei'],
            'RU' => ['name' => 'Russia', 'phone_code' => '+7', 'currency_code' => 'RUB', 'currency_symbol' => '₽'],

            'SA' => ['name' => 'Saudi Arabia', 'phone_code' => '+966', 'currency_code' => 'SAR', 'currency_symbol' => '﷼'],
            'SG' => ['name' => 'Singapore', 'phone_code' => '+65', 'currency_code' => 'SGD', 'currency_symbol' => '$'],
            'ZA' => ['name' => 'South Africa', 'phone_code' => '+27', 'currency_code' => 'ZAR', 'currency_symbol' => 'R'],

            'ES' => ['name' => 'Spain', 'phone_code' => '+34', 'currency_code' => 'EUR', 'currency_symbol' => '€'],
            'LK' => ['name' => 'Sri Lanka', 'phone_code' => '+94', 'currency_code' => 'LKR', 'currency_symbol' => 'Rs'],
            'SE' => ['name' => 'Sweden', 'phone_code' => '+46', 'currency_code' => 'SEK', 'currency_symbol' => 'kr'],
            'CH' => ['name' => 'Switzerland', 'phone_code' => '+41', 'currency_code' => 'CHF', 'currency_symbol' => 'Fr'],

            'TH' => ['name' => 'Thailand', 'phone_code' => '+66', 'currency_code' => 'THB', 'currency_symbol' => '฿'],
            'TR' => ['name' => 'Turkey', 'phone_code' => '+90', 'currency_code' => 'TRY', 'currency_symbol' => '₺'],

            'UA' => ['name' => 'Ukraine', 'phone_code' => '+380', 'currency_code' => 'UAH', 'currency_symbol' => '₴'],
            'AE' => ['name' => 'United Arab Emirates', 'phone_code' => '+971', 'currency_code' => 'AED', 'currency_symbol' => 'د.إ'],
            'GB' => ['name' => 'United Kingdom', 'phone_code' => '+44', 'currency_code' => 'GBP', 'currency_symbol' => '£'],
            'US' => ['name' => 'United States', 'phone_code' => '+1', 'currency_code' => 'USD', 'currency_symbol' => '$'],

            'VN' => ['name' => 'Vietnam', 'phone_code' => '+84', 'currency_code' => 'VND', 'currency_symbol' => '₫'],

        ];
    }
}

if (!function_exists('detect_user_country')) {

    function detect_user_country()
    {
        // 1. Cloudflare (BEST - FREE)
        if (!empty($_SERVER["HTTP_CF_IPCOUNTRY"])) {
            return strtoupper($_SERVER["HTTP_CF_IPCOUNTRY"]);
        }

        $ip = $_SERVER['REMOTE_ADDR'] ?? null;

        if (!$ip) {
            return 'BD';
        }

        // 2. FREE public API (no token)
        try {
            $url = "http://ip-api.com/json/{$ip}";
            $response = @file_get_contents($url);

            if ($response) {
                $data = json_decode($response, true);

                if (!empty($data['countryCode'])) {
                    return strtoupper($data['countryCode']);
                }
            }
        } catch (\Exception $e) {
            // ignore error
        }

        // 3. fallback
        return 'BD';
    }
}