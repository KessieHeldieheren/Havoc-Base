<?php
defined('BASEPATH') OR exit('No direct script access allowed');

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
 *  base_a_numerals [ "0", "1", "2", "3", "4", "5", "6", "7", "8", "9" ],
 *  base_b_numerals [ "X", "E", "D", "T", "N", "F", "H", "K", "V", "L", "A", "Q" ],
 *  base_a_orderscount [ 3 ],
 *  base_b_orderscount [ 3 ],
 *  base_a_ordersseperator [ "," ],
 *  base_b_ordersseperator [ " " ],
 *  base_a_fractionaldelimiter [ "." ],
 *  base_b_fractionaldelimiter [ ";" ]
 * ]
 *
 * - base_a_numerals: Numerals for Base A.
 * - base_b_numerals: Numerals for Base B.
 * - base_a_orderscount: Digit grouping count when rendering numbers with formatting. Example: 1000 -> 1,000
 * - base_b_orderscount: Digit grouping count when rendering numbers with formatting. Example: EXXX -> E XXX
 * - base_a_ordersseperator: Digit grouping seperator. See above.
 * - base_b_ordersseperator: Digit grouping seperator. See above.
 * - base_a_fractionaldelimiter: Delimiter for fractions. Example: 0 + 33 -> 0.33
 * - base_b_fractionaldelimiter: Delimiter for fractions. Example: X + N -> X;N
 *
 * Use Havoc_Base->convertAb() to convert base A into base B.
 * Use Havoc_Base->convertBa() to convert base B into base A.
 *
 * Once setup, you may provide numbers in the format given (except without an orders seperator).
 * I.e. giving EXXX;H to convert into 1728.5.
 *
 * Fractional conversions comply with the precision provided. 0.500 will yield X;HXX.
 *
 * Both take the following flags:
 * - convert_to_base_numerals: true returns the result in the appropriate numerals instead of decimal numbers
 * that represent the index of the those numerals.
 *
 * - return_array: true returns an array. False returns a concatenated string of the numerals.
 *
 * - format_numeric: true returns a numerically formatted string, including 'thousands' separators.
 *
 * @TODO Format a number input given by a user. Convenience method.
 *
 * @author Kessie Heldieheren <me@kessie.gold>
 * @package Havoc
 * @version 2.3
 */
class Havoc_Base
{
	/* Titles */
	const EF_CANNOT_INSTANTIATE_CLASS = "Cannot instantiate HavocBase because %s.";
	const EF_CANNOT_CONVERT_FROM_BASE = "Cannot convert anything from a base because %s.";
	const EF_CANNOT_SET_BASE_A_NUMERALS = "Cannot set %s's numerals list because %s.";
	const EF_CANNOT_SET_BASE_B_NUMERALS = "Cannot set %s's numerals list because %s.";
	const EF_CANNOT_CONVERT_AB = "Cannot begin a conversion from %s to %s because %s.";
	const EF_CANNOT_CONVERT_BA = "Cannot begin a conversion from %s to %s because %s.";
	const EF_CANNOT_INTDIV = "Cannot perform division on the input because %s.";

	/* Messages */
	const E_BASE_A_NUMERALS_EMPTY = "the first parameter is empty or not an array";
	const E_DIGITS_ARRAY_EMPTY = "the array of digits is empty";
	const E_NUMERALS_LIST_NOT_UNIQUE = "the numerals list contains duplicate symbols";
	const E_NO_NUMBER = "no number was provided";
	const E_NUMERALS_INVALID = "the number provided is not using the correct numerals";
	const E_INPUT_EXCEEDS_INT_LIMIT = "the number provided is too long";

	/**
	 * Base A's name.
	 *
	 * @var string
	 */
	private $baseAName = "Base A";

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
	 * Base A's fractional delimiter.
	 *
	 * @var string
	 */
	private $baseAFractionalDelimiter = ";";

	/**
	 * After how many orders to place a seperator.
	 *
	 * Note: this is equivalent to a decimal thousands seperator.
	 *
	 * @var int
	 */
	private $baseAOrdersCount = 3;

	/**
	 * Base B's name.
	 *
	 * @var string
	 */
	private $baseBName = "Base B";

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
	 * Base B's fractional delimiter.
	 *
	 * @var string
	 */
	private $baseBFractionalDelimiter = ".";

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

		if (!empty($params["base_a_name"])) {
			$this->setBaseAName($params["base_a_name"]);
		}

		if (!empty($params["base_b_name"])) {
			$this->setBaseBName($params["base_b_name"]);
		}

		if (!empty($params["base_a_orderscount"])) {
			$this->setBaseAOrdersCount($params["base_a_orderscount"]);
		}

		if (!empty($params["base_b_orderscount"])) {
			$this->setBaseBOrdersCount($params["base_b_orderscount"]);
		}

		if (!empty($params["base_a_ordersseperator"])) {
			$this->setBaseAOrdersSeperator($params["base_a_ordersseperator"]);
		}

		if (!empty($params["base_b_ordersseperator"])) {
			$this->setBaseBOrdersSeperator($params["base_b_ordersseperator"]);
		}

		if (!empty($params["base_a_fractionaldelimiter"])) {
			$this->setBaseAFractionalDelimiter($params["base_a_fractionaldelimiter"]);
		}

		if (!empty($params["base_b_fractionaldelimiter"])) {
			$this->setBaseBFractionalDelimiter($params["base_b_fractionaldelimiter"]);
		}
	}

	/**
	 * Converts a number in base A into base B, using Base B's numerals instead of an integer.
	 *
	 * Method also takes fractions now.
	 *
	 * @param string $number
	 * @param bool $convert_to_base_numerals
	 * @param bool $return_array
	 * @param bool $format_numeric
	 * @return array|string
	 */
	public function convertAb(
		string $number,
		bool $convert_to_base_numerals = true,
		bool $return_array = true,
		bool $format_numeric = false
	) {
		$hostDelimiter = $this->getBaseAFractionalDelimiter();
		$targetDelimiter = $this->getBaseBFractionalDelimiter();
		$hostOrdersSeperator = $this->getBaseAOrdersSeperator();
		$hostBase = $this->getBaseARadix();
		$targetBase = $this->getBaseBRadix();
		$validNumerals = array_merge($this->getBaseANumerals(), [$hostDelimiter, $hostOrdersSeperator]);
		$numberAsArray = str_split($number);

		try {
			if ("" === $number) {
				throw new OutOfBoundsException(self::E_NO_NUMBER);
			}

			if (false === $this->validateNumberString($numberAsArray, $validNumerals)) {
				throw new OutOfBoundsException(self::E_NUMERALS_INVALID);
			}
		} catch (RuntimeException $re) {
			throw new RuntimeException(
				sprintf(
					self::EF_CANNOT_CONVERT_AB,
					$this->getBaseAName(),
					$this->getBaseBName(),
					$re->getMessage()
				)
			);
		}

		$components = $this->splitNumberIntoComponents($number, $hostDelimiter);
		$number = str_replace($hostOrdersSeperator, "", $components[0]);

		if (isset($components[1])) {
			$fraction = str_split($components[1]);
			$fraction_digits = $this->renderNumeralsInBaseAByDigits($fraction);

			$resolution = count($fraction_digits);
			$baseAToDec = $this->convertFractionBaseToDec($fraction_digits, $hostBase);

			$result_fraction = $this->renderDigitsInBaseBNumerals(
				$this->convertFractionDecToBase($baseAToDec, $resolution, $targetBase)
			);
		}

		$number = str_split($number, 1);
		$number_digits = $this->renderNumeralsInBaseAByDigits($number);

		$result_digits = $this->convertArrayAb(
			$number_digits,
			$convert_to_base_numerals,
			$return_array,
			$format_numeric
		);

		if (isset($result_fraction)) {
			if (false === $return_array) {
				$result_fraction = implode("", $result_fraction);

				return ($result_digits . $targetDelimiter . $result_fraction);
			}

			array_push($result_digits, $targetDelimiter);

			return (array_merge($result_digits, $result_fraction));
		}

		return $result_digits;
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
	private function convertArrayAb(
		array $number,
		bool $convert_to_base_numerals = true,
		bool $return_array = true,
		bool $format_numeric = false
	) {
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
	 * Converts a number in base B into base A, using Base A's numerals instead of an integer.
	 *
	 * @param string $number
	 * @param bool $convert_to_base_numerals
	 * @param bool $return_array
	 * @param bool $format_numeric
	 * @return array|string
	 */
	public function convertBa(
		string $number,
		bool $convert_to_base_numerals = true,
		bool $return_array = true,
		bool $format_numeric = false
	) {
		$hostDelimiter = $this->getBaseBFractionalDelimiter();
		$targetDelimiter = $this->getBaseAFractionalDelimiter();
		$hostOrdersSeperator = $this->getBaseBOrdersSeperator();
		$hostBase = $this->getBaseBRadix();
		$targetBase = $this->getBaseARadix();
		$validNumerals = array_merge($this->getBaseBNumerals(), [$hostDelimiter, $hostOrdersSeperator]);
		$numberAsArray = str_split($number);

		try {
			if ("" === $number) {
				throw new OutOfBoundsException(self::E_NO_NUMBER);
			}

			if (false === $this->validateNumberString($numberAsArray, $validNumerals)) {
				throw new OutOfBoundsException(self::E_NUMERALS_INVALID);
			}
		} catch (RuntimeException $re) {
			throw new RuntimeException(
				sprintf(
					self::EF_CANNOT_CONVERT_BA,
					$this->getBaseBName(),
					$this->getBaseAName(),
					$re->getMessage()
				)
			);
		}

		$components = $this->splitNumberIntoComponents($number, $hostDelimiter);
		$number = str_replace($hostOrdersSeperator, "", $components[0]);

		if (isset($components[1])) {
			$fraction = str_split($components[1]);
			$fraction_digits = $this->renderNumeralsInBaseBByDigits($fraction);

			$resolution = count($fraction_digits);
			$baseAToDec = $this->convertFractionBaseToDec($fraction_digits, $hostBase);

			$result_fraction = $this->renderDigitsInBaseANumerals(
				$this->convertFractionDecToBase($baseAToDec, $resolution, $targetBase)
			);
		}

		$number = str_split($number, 1);
		$number_digits = $this->renderNumeralsInBaseBByDigits($number);

		$result_digits = $this->convertArrayBa(
			$number_digits,
			$convert_to_base_numerals,
			$return_array,
			$format_numeric
		);

		if (isset($result_fraction)) {
			if (false === $return_array) {
				$result_fraction = implode("", $result_fraction);

				return ($result_digits . $targetDelimiter . $result_fraction);
			}

			array_push($result_digits, $targetDelimiter);

			return (array_merge($result_digits, $result_fraction));
		}

		return $result_digits;
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
	private function convertArrayBa(
		array $number,
		bool $convert_to_base_numerals = true,
		bool $return_array = true,
		bool $format_numeric = false
	) {
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
	 * Convert a fraction in Base A to decimal.
	 *
	 * This is a preliminary conversion. Conversions are in two parts. See the method below this one.
	 *
	 * @param array $fraction
	 * @param int $base
	 * @return float
	 */
	private function convertFractionBaseToDec(array $fraction = [], $base): float
	{
		# Prevents a rounding error.
		array_push($fraction, 0);
		$resolution = count($fraction);
		$iteration = -1;
		$pointer = 0;

		foreach ($fraction as $digit) {
			$pointer = $pointer + $digit * $base ** $iteration;
			$iteration--;
		}

		return round($pointer, $resolution);
	}

	/**
	 * Convert a fraction in decimal to Base A.
	 *
	 * This is the final part of fraction conversions.
	 *
	 * @param $fraction
	 * @param int $resolution
	 * @param int $base
	 * @return array
	 */
	private function convertFractionDecToBase($fraction, $resolution = 1, $base): array
	{
		$result = [];
		$pointer = $fraction * $base;

		for ($i = 1; $i <= $resolution; $i++) {
			if ($i === 1) {
				array_push($result, floor($pointer));
				continue;
			}

			$truncated = $pointer - (int) $pointer;
			$pointer = $truncated * $base;

			if ($i === $resolution) {
				$resolved = round($pointer, 0, PHP_ROUND_HALF_UP);

				# TODO This should be looked at.
				# Converting 0.33 to dozenal yields a 2nd decimal place index of [12] when it rounds up.
				# This clamps any decimal places to within the bounds of their respective radix.
				# Such as in the example above, where [12] is clamped to [11]. [12] is not an index in base 12.
				if ($resolved >= $base) {
					$resolved = $resolved - 1;
				}

				array_push($result, $resolved);
				continue;
			}

			array_push($result, floor($pointer));
		}

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

			# TODO Not having to suppress this error would be nice.
			# Triggers a warning when giving a float because PHP converts huge ints to floats.
			$number = @intdiv($number, $base);
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
	 * Splits a number string into two parts: the units and fraction.
	 *
	 * @param $string
	 * @param $delimiter
	 * @return array
	 */
	private function splitNumberIntoComponents($string, $delimiter): array
	{
		return explode($delimiter, $string);
	}

	/**
	 * Returns false if the number string given contains invalid numerals.
	 *
	 * @param array $number
	 * @param array $numerals
	 * @return bool
	 */
	private function validateNumberString($number, $numerals): bool
	{
		$result = array_udiff($number, $numerals, "strcasecmp");

		if (count($result) > 0) {
			return false;
		}

		return true;
	}

	/**
	 * Returns Base A's Name.
	 *
	 * @return string
	 */
	public function getBaseAName(): string
	{
		return $this->baseAName;
	}

	/**
	 * Sets Base A's Name.
	 *
	 * @param string $name
	 */
	public function setBaseAName ($name)
	{
		$this->baseAName = $name;
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
					$this->getBaseAName(),
					$re->getMessage()
				)
			);
		}

		$this->baseANumerals = $numerals;
	}

	/**
	 * Returns Base B's Name.
	 *
	 * @return string
	 */
	public function getBaseBName(): string
	{
		return $this->baseBName;
	}

	/**
	 * Sets Base B's Name.
	 *
	 * @param string $name
	 */
	public function setBaseBName ($name)
	{
		$this->baseBName = $name;
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
					$this->getBaseBName(),
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
	 * Returns Base A's fractional delimiter.
	 *
	 * @return string
	 */
	public function getBaseAFractionalDelimiter(): string
	{
		return $this->baseAFractionalDelimiter;
	}

	/**
	 * Sets Base A's fractional delimiter.
	 *
	 * @param string $delimiter
	 */
	public function setBaseAFractionalDelimiter(string $delimiter)
	{
		$this->baseAFractionalDelimiter = $delimiter;
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
	 * Returns Base B's fractional delimiter.
	 *
	 * @return string
	 */
	public function getBaseBFractionalDelimiter(): string
	{
		return $this->baseBFractionalDelimiter;
	}

	/**
	 * Sets Base B's fractional delimiter.
	 *
	 * @param string $delimiter
	 */
	public function setBaseBFractionalDelimiter(string $delimiter)
	{
		$this->baseBFractionalDelimiter = $delimiter;
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
