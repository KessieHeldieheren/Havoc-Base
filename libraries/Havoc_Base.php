<?php

/**
 * Base conversions class.
 *
 * Allows converting between arbitrary bases in positional numeral systems, given arbitrary numerals.
 *
 * This is intended to work with CodeIgniter 3 as a library,
 * so the constructor takes an array of parameters.
 *
 * Provide one parameter with a key named base_a_numerals, and an auto-indexed array of the numerals.
 * Do the same for an array key named base_b_numerals.
 *
 * $params = [
 *  base_a_numerals [ "0", "1" ],
 *  base_b_numerals [ "A", "B", "C", "D" ]
 * ]
 *
 * Use Havoc_Base->convertAb() to convert base A into base B.
 * Use Havoc_Base->convertBa() to convert base B into base A.
 *
 * Both take the following flags:
 * - convert_to_base_numerals: true returns the result in the appropriate numerals instead of decimal numbers
 * that represent the index of the those numerals.
 *
 * - return_array: true returns an array. False returns a concatenated string of the numerals.
 *
 * - format_numeric: true returns a numerically formatted string, including 'thousands' separators.
 *
 * @todo Implement calculations for decimal/fractional numbers, and not just integers.
 *
 *
 * @author Kessie Heldieheren <kessie.gold>
 * @version 1
 */
class Havoc_Base
{
    /* Titles */
    const EF_CANNOT_INSTANTIATE_CLASS = "Cannot instantiate HavocBase because %s.";
    const EF_CANNOT_CONVERT_FROM_BASE = "Cannot convert anything from a base because %s.";
    const EF_CANNOT_SET_BASE_A_NUMERALS = "Cannot set Base A's numerals list because %s.";
    const EF_CANNOT_SET_BASE_B_NUMERALS = "Cannot set Base B's numerals list because %s.";

    /* Messages */
    const E_BASE_A_NUMERALS_EMPTY = "the first parameter is empty or not an array";
    const E_DIGITS_ARRAY_EMPTY = "the array of digits is empty";
    const E_NUMERALS_LIST_NOT_UNIQUE = "the numerals list contains duplicate symbols";

    /**
     * Base A radix value.
     *
     * @var int
     */
    private $baseARadix = 0;

    /**
     * Base A numerals list.
     *
     * @var array
     */
    private $baseANumerals = [];

    /**
     * Base A's orders separator.
     *
     * Note: this is equivalent to a decimal thousands seperator.
     *
     * @var string
     */
    private $baseAOrdersSeperator = " ";

    /**
     * After how many orders to place a seperator.
     *
     * Note: this is equivalent to a decimal thousands seperator.
     *
     * @var int
     */
    private $baseAOrdersCount = 3;

    /**
     * Base B radix value.
     *
     * @var int
     */
    private $baseBRadix = 10;

    /**
     * Base B numerals list.
     *
     * @var array
     */
    private $baseBNumerals = [
        0,
        1,
        2,
        3,
        4,
        5,
        6,
        7,
        8,
        9
    ];

    /**
     * Base B's orders separator.
     *
     * Note: this is equivalent to a decimal thousands seperator.
     *
     * @var string
     */
    private $baseBOrdersSeperator = ",";

    /**
     * After how many orders to place a seperator.
     *
     * Note: this is equivalent to a decimal thousands seperator.
     *
     * @var int
     */
    private $baseBOrdersCount = 3;

    /**
     * HavocBase constructor.
     *
     * This is intended to work with CodeIgniter 3 as a library,
     * so the constructor takes an array of parameters.
     *
     * Provide one parameter with a key named base_a_numerals, and an auto-indexed array of the numerals.
     * Do the same for an array key named base_b_numerals.
     *
     * $params = [
     *  base_a_numerals [ "0", "1" ],
     *  base_b_numerals [ "A", "B", "C", "D" ]
     * ]
     *
     * @param array $params
     * @throws RuntimeException
     */
    public function __construct(array $params)
    {
        try {
            if (empty($params["base_a_numerals"])) {
                throw new OutOfBoundsException(self::E_BASE_A_NUMERALS_EMPTY);
            }
        } catch (RuntimeException $re) {
            throw new RuntimeException(
                sprintf(
                    self::EF_CANNOT_INSTANTIATE_CLASS,
                    $re->getMessage()
                )
            );
        }

        $this->setBaseANumerals($params["base_a_numerals"]);
        $this->setBaseARadix();

        if (!empty($params["base_b_numerals"])) {
            $this->setBaseBNumerals($params["base_b_numerals"]);
            $this->setBaseBRadix();
        }
    }

    /**
     * Converts a number in base A into base B.
     *
     * @param array $number
     * @param bool $convert_to_base_numerals
     * @param bool $return_array
     * @param bool $format_numeric
     * @return array|string
     */
    public function convertArrayAb(array $number, bool $convert_to_base_numerals = true, bool $return_array = true, bool $format_numeric = false)
    {
        $result = $this->convertBaseAb($number);

        if ($convert_to_base_numerals) {
            $result = $this->renderDigitsInBaseBNumerals($result);
        }

        if ($format_numeric) {
            $result = $this->formatBaseBNumeric($result);
        }

        if ($return_array) {
            return $result;
        }

        return implode("", $result);
    }

    /**
     * Converts a number in base A into base B, using Base B's numerals instead of an integer.
     *
     * @param $number
     * @param bool $convert_to_base_numerals
     * @param bool $return_array
     * @param bool $format_numeric
     * @return array|string
     */
    public function convertAb($number, bool $convert_to_base_numerals = true, bool $return_array = true, bool $format_numeric = false)
    {
        $number = $digits = str_split($number, 1);

        $digits = $this->renderNumeralsInBaseAByDigits($number);

        $result = $this->convertArrayAb(
            $digits,
            $convert_to_base_numerals,
            $return_array,
            $format_numeric
        );

        return $result;
    }

    /**
     * Converts a number in base B into base A.
     *
     * @param array $number
     * @param bool $convert_to_base_numerals
     * @param bool $return_array
     * @param bool $format_numeric
     * @return array|string
     */
    public function convertArrayBa(array $number, bool $convert_to_base_numerals = true, bool $return_array = true, bool $format_numeric = false)
    {
        $result = $this->convertBaseBa($number);

        if ($convert_to_base_numerals) {
            $result = $this->renderDigitsInBaseANumerals($result);
        }

        if ($format_numeric) {
            $result = $this->formatBaseANumeric($result);
        }

        if ($return_array) {
            return $result;
        }

        return implode("", $result);
    }

    /**
     * Converts a number in base B into base A, using Base A's numerals instead of an integer.
     *
     * @param $number
     * @param bool $convert_to_base_numerals
     * @param bool $return_array
     * @param bool $format_numeric
     * @return array|string
     */
    public function convertBa($number, bool $convert_to_base_numerals = true, bool $return_array = true, bool $format_numeric = false)
    {
        $number = $digits = str_split($number, 1);

        $digits = $this->renderNumeralsInBaseBByDigits($number);

        $result = $this->convertArrayBa(
            $digits,
            $convert_to_base_numerals,
            $return_array,
            $format_numeric
        );

        return $result;
    }

    /**
     * Convert a number to a specific base.
     *
     * @param float $number
     * @param int $base
     * @return array
     */
    private function convertToBase(float $number, int $base): array
    {
        $digits = [];

        while ($number > 0) {
            $result = $number % $base;

            array_push($digits, $result);

            $number = intdiv($number, $base);
        }

        if (empty($digits)) {
            $digits[0] = 0;
        }

        return array_reverse($digits);
    }

    /**
     * Convert a number from a specific base.
     *
     * @param array $digits
     * @param int $base
     * @return int
     * @throws RuntimeException
     */
    private function convertFromBase(array $digits, int $base)
    {
        try {
            if (empty($digits)) {
                throw new OutOfBoundsException(self::E_DIGITS_ARRAY_EMPTY);
            }
        } catch (RuntimeException $re) {
            throw new RuntimeException(
                sprintf(
                    self::EF_CANNOT_CONVERT_FROM_BASE,
                    $re->getMessage()
                )
            );
        }

        $number = 0;

        foreach ($digits as $digit) {
            $number = $base * $number + $digit;
        }

        return $number;
    }

    /**
     * Convert a number in base A into base B.
     *
     * @param array $digits
     * @return array
     */
    private function convertBaseAb(array $digits): array
    {
        $hostBase = $this->getBaseARadix();
        $targetBase = $this->getBaseBRadix();
        $converted = $this->convertFromBase($digits, $hostBase);

        return(
        $this->convertToBase(
            $converted,
            $targetBase
        )
        );
    }

    /**
     * Convert a number in base B into Base A.
     *
     * @param array $digits
     * @return array
     */
    private function convertBaseBa(array $digits): array
    {
        $hostBase = $this->getBaseBRadix();
        $targetBase = $this->getBaseARadix();
        $converted = $this->convertFromBase($digits, $hostBase);

        return(
        $this->convertToBase(
            $converted,
            $targetBase
        )
        );
    }

    /**
     * Render given digits in their native Base A numerals.
     *
     * @param array $digits
     * @return array
     */
    private function renderDigitsInBaseANumerals(array $digits): array
    {
        $result = [];
        $numerals = $this->getBaseANumerals();

        foreach ($digits as $key => $value) {
            array_push($result, $numerals[$value]);
        }

        return $result;
    }

    /**
     * Render numbers in Base A using integers.
     *
     * @param array $digits
     * @return array
     */
    private function renderNumeralsInBaseAByDigits(array $digits): array
    {
        $result = [];
        $numerals = $this->getBaseANumerals();

        foreach ($digits as $key => $value) {
            $digit = array_search($value, $numerals);

            array_push($result, $digit);
        }

        return $result;
    }

    /**
     * Render given digits in their native Base B numerals.
     *
     * @param array $digits
     * @return array
     */
    private function renderDigitsInBaseBNumerals(array $digits): array
    {
        $result = [];
        $numerals = $this->getBaseBNumerals();

        foreach ($digits as $key => $value) {
            array_push($result, $numerals[$value]);
        }

        return $result;
    }

    /**
     * Render numbers in Base A using integers.
     *
     * @param array $digits
     * @return array
     */
    private function renderNumeralsInBaseBByDigits(array $digits): array
    {
        $result = [];
        $numerals = $this->getBaseBNumerals();

        foreach ($digits as $key => $value) {
            $digit = array_search($value, $numerals);

            array_push($result, $digit);
        }

        return $result;
    }

    /**
     * Adds a space between every three digits on a Base A number.
     *
     * @param $digits
     * @return array
     */
    private function formatBaseANumeric(array $digits): array
    {
        $orders_seperator = $this->getBaseAOrdersSeperator();
        $orders_count = $this->getBaseAOrdersCount();

        $digits = array_chunk(array_reverse($digits), $orders_count);
        $result = [];

        foreach ($digits as $set) {
            foreach ($set as $digit) {
                array_push($result, $digit);
            }

            array_push($result, $orders_seperator);
        }

        $result_keys = array_keys($result);
        $result_end = end($result_keys);

        if ($orders_seperator === $result[$result_end]) {
            array_pop($result);
        }

        return array_reverse($result);
    }

    /**
     * Adds a space between every three digits on a Base B number.
     *
     * @param $digits
     * @return array
     */
    private function formatBaseBNumeric(array $digits): array
    {
        $orders_seperator = $this->getBaseBOrdersSeperator();
        $orders_count = $this->getBaseBOrdersCount();

        $digits = array_chunk(array_reverse($digits), $orders_count);
        $result = [];

        foreach ($digits as $set) {
            foreach ($set as $digit) {
                array_push($result, $digit);
            }

            array_push($result, $orders_seperator);
        }

        $result_keys = array_keys($result);
        $result_end = end($result_keys);

        if ($orders_seperator === $result[$result_end]) {
            array_pop($result);
        }

        return array_reverse($result);
    }

    /**
     * Returns Base A Radix.
     *
     * @return int
     */
    public function getBaseARadix(): int
    {
        return $this->baseARadix;
    }

    /**
     * Sets base A Radix.
     */
    protected function setBaseARadix()
    {
        $this->baseARadix = count($this->getBaseANumerals());
    }

    /**
     * Returns Base A Numerals.
     *
     * @return array
     */
    public function getBaseANumerals(): array
    {
        return $this->baseANumerals;
    }

    /**
     * Sets Base A Numerals.
     *
     * @param array $numerals
     * @throws RuntimeException
     */
    public function setBaseANumerals(array $numerals)
    {
        try {
            if (count($numerals) !== count(array_flip($numerals))) {
                throw new OutOfBoundsException(self::E_NUMERALS_LIST_NOT_UNIQUE);
            }
        } catch (RuntimeException $re) {
            throw new RuntimeException(
                sprintf(
                    self::EF_CANNOT_SET_BASE_A_NUMERALS,
                    $re->getMessage()
                )
            );
        }

        $this->baseANumerals = $numerals;
    }

    /**
     * Returns Base B Radix.
     *
     * @return int
     */
    public function getBaseBRadix(): int
    {
        return $this->baseBRadix;
    }

    /**
     * Sets Base B Radix.
     */
    protected function setBaseBRadix()
    {
        $this->baseBRadix = count($this->getBaseBNumerals());
    }

    /**
     * Returns Base B Numerals.
     *
     * @return array
     */
    public function getBaseBNumerals(): array
    {
        return $this->baseBNumerals;
    }

    /**
     * Sets Base B Numerals.
     *
     * @param array $numerals
     * @throws RuntimeException
     */
    public function setBaseBNumerals(array $numerals)
    {
        try {
            if (count($numerals) !== count(array_flip($numerals))) {
                throw new OutOfBoundsException(self::E_NUMERALS_LIST_NOT_UNIQUE);
            }
        } catch (RuntimeException $re) {
            throw new RuntimeException(
                sprintf(
                    self::EF_CANNOT_SET_BASE_B_NUMERALS,
                    $re->getMessage()
                )
            );
        }

        $this->baseBNumerals = $numerals;
    }

    /**
     * Returns Base A's orders seperator.
     *
     * @return string
     */
    public function getBaseAOrdersSeperator(): string
    {
        return $this->baseAOrdersSeperator;
    }

    /**
     * Sets Base A's orders seperator.
     *
     * @param string $seperator
     */
    public function setBaseAOrdersSeperator(string $seperator)
    {
        $this->baseAOrdersSeperator = $seperator;
    }

    /**
     * Returns Base A's orders seperator.
     *
     * @return int
     */
    public function getBaseAOrdersCount(): int
    {
        return $this->baseAOrdersCount;
    }

    /**
     * Sets Base A's orders seperator.
     *
     * @param int $count
     */
    public function setBaseAOrdersCount(int $count)
    {
        $this->baseAOrdersCount = $count;
    }

    /**
     * Returns Base B's orders seperator.
     *
     * @return string
     */
    public function getBaseBOrdersSeperator(): string
    {
        return $this->baseBOrdersSeperator;
    }

    /**
     * Sets Base B's orders seperator.
     *
     * @param string $seperator
     */
    public function setBaseBOrdersSeperator(string $seperator)
    {
        $this->baseBOrdersSeperator = $seperator;
    }

    /**
     * Returns Base B's orders seperator.
     *
     * @return int
     */
    public function getBaseBOrdersCount(): int
    {
        return $this->baseBOrdersCount;
    }

    /**
     * Sets Base B's orders seperator.
     *
     * @param int $count
     */
    public function setBaseBOrdersCount(int $count)
    {
        $this->baseBOrdersCount = $count;
    }
}
