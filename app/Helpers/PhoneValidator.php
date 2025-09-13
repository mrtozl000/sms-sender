<?php

namespace App\Helpers;

use libphonenumber\NumberParseException;
use libphonenumber\PhoneNumberUtil;
use libphonenumber\PhoneNumberFormat;

class PhoneValidator
{
    private static ?PhoneNumberUtil $phoneUtil = null;

    /**
     * PhoneNumberUtil instance'ı al (Singleton)
     */
    private static function getPhoneUtil(): PhoneNumberUtil
    {
        if (self::$phoneUtil === null) {
            self::$phoneUtil = PhoneNumberUtil::getInstance();
        }
        return self::$phoneUtil;
    }

    /**
     * Telefon numarasını validate et
     *
     * @param string $phoneNumber
     * @param string $defaultRegion (TR, US, GB vb.)
     * @return array ['valid' => bool, 'formatted' => string|null, 'error' => string|null]
     */
    public static function validate(string $phoneNumber, string $defaultRegion = 'TR'): array
    {
        try {
            $phoneUtil = self::getPhoneUtil();
            $numberProto = $phoneUtil->parse($phoneNumber, $defaultRegion);

            $isValid = $phoneUtil->isValidNumber($numberProto);

            if ($isValid) {
                // Uluslararası formata çevir
                $formatted = $phoneUtil->format($numberProto, PhoneNumberFormat::E164);

                return [
                    'valid' => true,
                    'formatted' => $formatted,
                    'country_code' => $numberProto->getCountryCode(),
                    'national_number' => $numberProto->getNationalNumber(),
                    'error' => null
                ];
            }

            return [
                'valid' => false,
                'formatted' => null,
                'error' => 'Invalid phone number'
            ];

        } catch (NumberParseException $e) {
            return [
                'valid' => false,
                'formatted' => null,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * @param string $phoneNumber
     * @return bool
     */
    public static function isTurkishNumber(string $phoneNumber): bool
    {
        $result = self::validate($phoneNumber, 'TR');
        return $result['valid'] && $result['country_code'] == 90;
    }

    /**
     * @param string $phoneNumber
     * @param string $defaultRegion
     * @return string|null
     */
    public static function format(string $phoneNumber, string $defaultRegion = 'TR'): ?string
    {
        $result = self::validate($phoneNumber, $defaultRegion);
        return $result['formatted'];
    }
}
