<?php

declare(strict_types=1);

namespace Utils\Phone;

final class PrefixChecker
{
    public final const COUNTRY_PREFIX = [
        [
            "value" => "AC - +247",
            "code" => "+247",
        ],
        [
            "value" => "AD - +376",
            "code" => "+376",
        ],
        [
            "value" => "AE - +971",
            "code" => "+971",
        ],
        [
            "value" => "AF - +93",
            "code" => "+93",
        ],
        [
            "value" => "AG - +1",
            "code" => "+1",
        ],
        [
            "value" => "AI - +1",
            "code" => "+1",
        ],
        [
            "value" => "AL - +355",
            "code" => "+355",
        ],
        [
            "value" => "AM - +374",
            "code" => "+374",
        ],
        [
            "value" => "AO - +244",
            "code" => "+244",
        ],
        [
            "value" => "AR - +54",
            "code" => "+54",
        ],
        [
            "value" => "AS - +1",
            "code" => "+1",
        ],
        [
            "value" => "AT - +43",
            "code" => "+43",
        ],
        [
            "value" => "AU - +61",
            "code" => "+61",
        ],
        [
            "value" => "AW - +297",
            "code" => "+297",
        ],
        [
            "value" => "AX - +358",
            "code" => "+358",
        ],
        [
            "value" => "AZ - +994",
            "code" => "+994",
        ],
        [
            "value" => "BA - +387",
            "code" => "+387",
        ],
        [
            "value" => "BB - +1",
            "code" => "+1",
        ],
        [
            "value" => "BD - +880",
            "code" => "+880",
        ],
        [
            "value" => "BE - +32",
            "code" => "+32",
        ],
        [
            "value" => "BF - +226",
            "code" => "+226",
        ],
        [
            "value" => "BG - +359",
            "code" => "+359",
        ],
        [
            "value" => "BH - +973",
            "code" => "+973",
        ],
        [
            "value" => "BI - +257",
            "code" => "+257",
        ],
        [
            "value" => "BJ - +229",
            "code" => "+229",
        ],
        [
            "value" => "BL - +590",
            "code" => "+590",
        ],
        [
            "value" => "BM - +1",
            "code" => "+1",
        ],
        [
            "value" => "BN - +673",
            "code" => "+673",
        ],
        [
            "value" => "BO - +591",
            "code" => "+591",
        ],
        [
            "value" => "BQ - +599",
            "code" => "+599",
        ],
        [
            "value" => "BR - +55",
            "code" => "+55",
        ],
        [
            "value" => "BS - +1",
            "code" => "+1",
        ],
        [
            "value" => "BT - +975",
            "code" => "+975",
        ],
        [
            "value" => "BW - +267",
            "code" => "+267",
        ],
        [
            "value" => "BY - +375",
            "code" => "+375",
        ],
        [
            "value" => "BZ - +501",
            "code" => "+501",
        ],
        [
            "value" => "CA - +1",
            "code" => "+1",
        ],
        [
            "value" => "CC - +61",
            "code" => "+61",
        ],
        [
            "value" => "CD - +243",
            "code" => "+243",
        ],
        [
            "value" => "CF - +236",
            "code" => "+236",
        ],
        [
            "value" => "CG - +242",
            "code" => "+242",
        ],
        [
            "value" => "CH - +41",
            "code" => "+41",
        ],
        [
            "value" => "CI - +225",
            "code" => "+225",
        ],
        [
            "value" => "CK - +682",
            "code" => "+682",
        ],
        [
            "value" => "CL - +56",
            "code" => "+56",
        ],
        [
            "value" => "CM - +237",
            "code" => "+237",
        ],
        [
            "value" => "CN - +86",
            "code" => "+86",
        ],
        [
            "value" => "CO - +57",
            "code" => "+57",
        ],
        [
            "value" => "CR - +506",
            "code" => "+506",
        ],
        [
            "value" => "CU - +53",
            "code" => "+53",
        ],
        [
            "value" => "CV - +238",
            "code" => "+238",
        ],
        [
            "value" => "CW - +599",
            "code" => "+599",
        ],
        [
            "value" => "CX - +61",
            "code" => "+61",
        ],
        [
            "value" => "CY - +357",
            "code" => "+357",
        ],
        [
            "value" => "CZ - +420",
            "code" => "+420",
        ],
        [
            "value" => "DE - +49",
            "code" => "+49",
        ],
        [
            "value" => "DJ - +253",
            "code" => "+253",
        ],
        [
            "value" => "DK - +45",
            "code" => "+45",
        ],
        [
            "value" => "DM - +1",
            "code" => "+1",
        ],
        [
            "value" => "DO - +1",
            "code" => "+1",
        ],
        [
            "value" => "DZ - +213",
            "code" => "+213",
        ],
        [
            "value" => "EC - +593",
            "code" => "+593",
        ],
        [
            "value" => "EE - +372",
            "code" => "+372",
        ],
        [
            "value" => "EG - +20",
            "code" => "+20",
        ],
        [
            "value" => "EH - +212",
            "code" => "+212",
        ],
        [
            "value" => "ER - +291",
            "code" => "+291",
        ],
        [
            "value" => "ES - +34",
            "code" => "+34",
        ],
        [
            "value" => "ET - +251",
            "code" => "+251",
        ],
        [
            "value" => "FI - +358",
            "code" => "+358",
        ],
        [
            "value" => "FJ - +679",
            "code" => "+679",
        ],
        [
            "value" => "FK - +500",
            "code" => "+500",
        ],
        [
            "value" => "FM - +691",
            "code" => "+691",
        ],
        [
            "value" => "FO - +298",
            "code" => "+298",
        ],
        [
            "value" => "FR - +33",
            "code" => "+33",
        ],
        [
            "value" => "GA - +241",
            "code" => "+241",
        ],
        [
            "value" => "GB - +44",
            "code" => "+44",
        ],
        [
            "value" => "GD - +1",
            "code" => "+1",
        ],
        [
            "value" => "GE - +995",
            "code" => "+995",
        ],
        [
            "value" => "GF - +594",
            "code" => "+594",
        ],
        [
            "value" => "GG - +44",
            "code" => "+44",
        ],
        [
            "value" => "GH - +233",
            "code" => "+233",
        ],
        [
            "value" => "GI - +350",
            "code" => "+350",
        ],
        [
            "value" => "GL - +299",
            "code" => "+299",
        ],
        [
            "value" => "GM - +220",
            "code" => "+220",
        ],
        [
            "value" => "GN - +224",
            "code" => "+224",
        ],
        [
            "value" => "GP - +590",
            "code" => "+590",
        ],
        [
            "value" => "GQ - +240",
            "code" => "+240",
        ],
        [
            "value" => "GR - +30",
            "code" => "+30",
        ],
        [
            "value" => "GT - +502",
            "code" => "+502",
        ],
        [
            "value" => "GU - +1",
            "code" => "+1",
        ],
        [
            "value" => "GW - +245",
            "code" => "+245",
        ],
        [
            "value" => "GY - +592",
            "code" => "+592",
        ],
        [
            "value" => "HK - +852",
            "code" => "+852",
        ],
        [
            "value" => "HN - +504",
            "code" => "+504",
        ],
        [
            "value" => "HR - +385",
            "code" => "+385",
        ],
        [
            "value" => "HT - +509",
            "code" => "+509",
        ],
        [
            "value" => "HU - +36",
            "code" => "+36",
        ],
        [
            "value" => "ID - +62",
            "code" => "+62",
        ],
        [
            "value" => "IE - +353",
            "code" => "+353",
        ],
        [
            "value" => "IL - +972",
            "code" => "+972",
        ],
        [
            "value" => "IM - +44",
            "code" => "+44",
        ],
        [
            "value" => "IN - +91",
            "code" => "+91",
        ],
        [
            "value" => "IO - +246",
            "code" => "+246",
        ],
        [
            "value" => "IQ - +964",
            "code" => "+964",
        ],
        [
            "value" => "IR - +98",
            "code" => "+98",
        ],
        [
            "value" => "IS - +354",
            "code" => "+354",
        ],
        [
            "value" => "IT - +39",
            "code" => "+39",
        ],
        [
            "value" => "JE - +44",
            "code" => "+44",
        ],
        [
            "value" => "JM - +1",
            "code" => "+1",
        ],
        [
            "value" => "JO - +962",
            "code" => "+962",
        ],
        [
            "value" => "JP - +81",
            "code" => "+81",
        ],
        [
            "value" => "KE - +254",
            "code" => "+254",
        ],
        [
            "value" => "KG - +996",
            "code" => "+996",
        ],
        [
            "value" => "KH - +855",
            "code" => "+855",
        ],
        [
            "value" => "KI - +686",
            "code" => "+686",
        ],
        [
            "value" => "KM - +269",
            "code" => "+269",
        ],
        [
            "value" => "KN - +1",
            "code" => "+1",
        ],
        [
            "value" => "KP - +850",
            "code" => "+850",
        ],
        [
            "value" => "KR - +82",
            "code" => "+82",
        ],
        [
            "value" => "KW - +965",
            "code" => "+965",
        ],
        [
            "value" => "KY - +1",
            "code" => "+1",
        ],
        [
            "value" => "KZ - +7",
            "code" => "+7",
        ],
        [
            "value" => "LA - +856",
            "code" => "+856",
        ],
        [
            "value" => "LB - +961",
            "code" => "+961",
        ],
        [
            "value" => "LC - +1",
            "code" => "+1",
        ],
        [
            "value" => "LI - +423",
            "code" => "+423",
        ],
        [
            "value" => "LK - +94",
            "code" => "+94",
        ],
        [
            "value" => "LR - +231",
            "code" => "+231",
        ],
        [
            "value" => "LS - +266",
            "code" => "+266",
        ],
        [
            "value" => "LT - +370",
            "code" => "+370",
        ],
        [
            "value" => "LU - +352",
            "code" => "+352",
        ],
        [
            "value" => "LV - +371",
            "code" => "+371",
        ],
        [
            "value" => "LY - +218",
            "code" => "+218",
        ],
        [
            "value" => "MA - +212",
            "code" => "+212",
        ],
        [
            "value" => "MC - +377",
            "code" => "+377",
        ],
        [
            "value" => "MD - +373",
            "code" => "+373",
        ],
        [
            "value" => "ME - +382",
            "code" => "+382",
        ],
        [
            "value" => "MF - +590",
            "code" => "+590",
        ],
        [
            "value" => "MG - +261",
            "code" => "+261",
        ],
        [
            "value" => "MH - +692",
            "code" => "+692",
        ],
        [
            "value" => "MK - +389",
            "code" => "+389",
        ],
        [
            "value" => "ML - +223",
            "code" => "+223",
        ],
        [
            "value" => "MM - +95",
            "code" => "+95",
        ],
        [
            "value" => "MN - +976",
            "code" => "+976",
        ],
        [
            "value" => "MO - +853",
            "code" => "+853",
        ],
        [
            "value" => "MP - +1",
            "code" => "+1",
        ],
        [
            "value" => "MQ - +596",
            "code" => "+596",
        ],
        [
            "value" => "MR - +222",
            "code" => "+222",
        ],
        [
            "value" => "MS - +1",
            "code" => "+1",
        ],
        [
            "value" => "MT - +356",
            "code" => "+356",
        ],
        [
            "value" => "MU - +230",
            "code" => "+230",
        ],
        [
            "value" => "MV - +960",
            "code" => "+960",
        ],
        [
            "value" => "MW - +265",
            "code" => "+265",
        ],
        [
            "value" => "MX - +52",
            "code" => "+52",
        ],
        [
            "value" => "MY - +60",
            "code" => "+60",
        ],
        [
            "value" => "MZ - +258",
            "code" => "+258",
        ],
        [
            "value" => "NA - +264",
            "code" => "+264",
        ],
        [
            "value" => "NC - +687",
            "code" => "+687",
        ],
        [
            "value" => "NE - +227",
            "code" => "+227",
        ],
        [
            "value" => "NF - +672",
            "code" => "+672",
        ],
        [
            "value" => "NG - +234",
            "code" => "+234",
        ],
        [
            "value" => "NI - +505",
            "code" => "+505",
        ],
        [
            "value" => "NL - +31",
            "code" => "+31",
        ],
        [
            "value" => "NO - +47",
            "code" => "+47",
        ],
        [
            "value" => "NP - +977",
            "code" => "+977",
        ],
        [
            "value" => "NR - +674",
            "code" => "+674",
        ],
        [
            "value" => "NU - +683",
            "code" => "+683",
        ],
        [
            "value" => "NZ - +64",
            "code" => "+64",
        ],
        [
            "value" => "OM - +968",
            "code" => "+968",
        ],
        [
            "value" => "PA - +507",
            "code" => "+507",
        ],
        [
            "value" => "PE - +51",
            "code" => "+51",
        ],
        [
            "value" => "PF - +689",
            "code" => "+689",
        ],
        [
            "value" => "PG - +675",
            "code" => "+675",
        ],
        [
            "value" => "PH - +63",
            "code" => "+63",
        ],
        [
            "value" => "PK - +92",
            "code" => "+92",
        ],
        [
            "value" => "PL - +48",
            "code" => "+48",
        ],
        [
            "value" => "PM - +508",
            "code" => "+508",
        ],
        [
            "value" => "PR - +1",
            "code" => "+1",
        ],
        [
            "value" => "PS - +970",
            "code" => "+970",
        ],
        [
            "value" => "PT - +351",
            "code" => "+351",
        ],
        [
            "value" => "PW - +680",
            "code" => "+680",
        ],
        [
            "value" => "PY - +595",
            "code" => "+595",
        ],
        [
            "value" => "QA - +974",
            "code" => "+974",
        ],
        [
            "value" => "RE - +262",
            "code" => "+262",
        ],
        [
            "value" => "RO - +40",
            "code" => "+40",
        ],
        [
            "value" => "RS - +381",
            "code" => "+381",
        ],
        [
            "value" => "RU - +7",
            "code" => "+7",
        ],
        [
            "value" => "RW - +250",
            "code" => "+250",
        ],
        [
            "value" => "SA - +966",
            "code" => "+966",
        ],
        [
            "value" => "SB - +677",
            "code" => "+677",
        ],
        [
            "value" => "SC - +248",
            "code" => "+248",
        ],
        [
            "value" => "SD - +249",
            "code" => "+249",
        ],
        [
            "value" => "SE - +46",
            "code" => "+46",
        ],
        [
            "value" => "SG - +65",
            "code" => "+65",
        ],
        [
            "value" => "SH - +290",
            "code" => "+290",
        ],
        [
            "value" => "SI - +386",
            "code" => "+386",
        ],
        [
            "value" => "SJ - +47",
            "code" => "+47",
        ],
        [
            "value" => "SK - +421",
            "code" => "+421",
        ],
        [
            "value" => "SL - +232",
            "code" => "+232",
        ],
        [
            "value" => "SM - +378",
            "code" => "+378",
        ],
        [
            "value" => "SN - +221",
            "code" => "+221",
        ],
        [
            "value" => "SO - +252",
            "code" => "+252",
        ],
        [
            "value" => "SR - +597",
            "code" => "+597",
        ],
        [
            "value" => "SS - +211",
            "code" => "+211",
        ],
        [
            "value" => "ST - +239",
            "code" => "+239",
        ],
        [
            "value" => "SV - +503",
            "code" => "+503",
        ],
        [
            "value" => "SX - +1",
            "code" => "+1",
        ],
        [
            "value" => "SY - +963",
            "code" => "+963",
        ],
        [
            "value" => "SZ - +268",
            "code" => "+268",
        ],
        [
            "value" => "TA - +290",
            "code" => "+290",
        ],
        [
            "value" => "TC - +1",
            "code" => "+1",
        ],
        [
            "value" => "TD - +235",
            "code" => "+235",
        ],
        [
            "value" => "TG - +228",
            "code" => "+228",
        ],
        [
            "value" => "TH - +66",
            "code" => "+66",
        ],
        [
            "value" => "TJ - +992",
            "code" => "+992",
        ],
        [
            "value" => "TK - +690",
            "code" => "+690",
        ],
        [
            "value" => "TL - +670",
            "code" => "+670",
        ],
        [
            "value" => "TM - +993",
            "code" => "+993",
        ],
        [
            "value" => "TN - +216",
            "code" => "+216",
        ],
        [
            "value" => "TO - +676",
            "code" => "+676",
        ],
        [
            "value" => "TR - +90",
            "code" => "+90",
        ],
        [
            "value" => "TT - +1",
            "code" => "+1",
        ],
        [
            "value" => "TV - +688",
            "code" => "+688",
        ],
        [
            "value" => "TW - +886",
            "code" => "+886",
        ],
        [
            "value" => "TZ - +255",
            "code" => "+255",
        ],
        [
            "value" => "UA - +380",
            "code" => "+380",
        ],
        [
            "value" => "UG - +256",
            "code" => "+256",
        ],
        [
            "value" => "US - +1",
            "code" => "+1",
        ],
        [
            "value" => "UY - +598",
            "code" => "+598",
        ],
        [
            "value" => "UZ - +998",
            "code" => "+998",
        ],
        [
            "value" => "VA - +39",
            "code" => "+39",
        ],
        [
            "value" => "VC - +1",
            "code" => "+1",
        ],
        [
            "value" => "VE - +58",
            "code" => "+58",
        ],
        [
            "value" => "VG - +1",
            "code" => "+1",
        ],
        [
            "value" => "VI - +1",
            "code" => "+1",
        ],
        [
            "value" => "VN - +84",
            "code" => "+84",
        ],
        [
            "value" => "VU - +678",
            "code" => "+678",
        ],
        [
            "value" => "WF - +681",
            "code" => "+681",
        ],
        [
            "value" => "WS - +685",
            "code" => "+685",
        ],
        [
            "value" => "XK - +383",
            "code" => "+383",
        ],
        [
            "value" => "YE - +967",
            "code" => "+967",
        ],
        [
            "value" => "YT - +262",
            "code" => "+262",
        ],
        [
            "value" => "ZA - +27",
            "code" => "+27",
        ],
        [
            "value" => "ZM - +260",
            "code" => "+260",
        ],
        [
            "value" => "ZW - +263",
            "code" => "+263",
        ],
    ];

    public static function getAllPrefixes(): array
    {
        $prefixes = [];
        foreach (self::COUNTRY_PREFIX as $item) {
            $prefixes[] = $item['code'];
        }

        return $prefixes;
    }

    public static function isPrefixValid(string $prefix): bool
    {
        return in_array($prefix, self::getAllPrefixes());
    }
}
