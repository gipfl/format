<?php

namespace gipfl\Format;

use DateTimeZone;
use IntlDateFormatter;
use IntlTimeZone;
use RuntimeException;
use function in_array;

class LocalTime
{
    /** @var IntlDateFormatter */
    protected $formatter;

    /** @var IntlDateFormatter */
    protected $dayFormatter;

    /** @var string */
    protected $locale;

    /** @var DateTimeZone|IntlTimeZone */
    protected $timezone;

    public function setLocale($locale)
    {
        if ($this->locale !== $locale) {
            $this->locale = $locale;
            $this->reset();
        }
    }

    /**
     * @param DateTimeZone|IntlTimeZone $timezone
     * @return void
     */
    public function setTimezone($timezone)
    {
        // Hint: type checking is delegated to timeZonesAreEqual
        if (self::timeZonesAreEqual($this->timezone, $timezone)) {
            return;
        }

        $this->timezone = $timezone;
        $this->reset();
    }

    /**
     * For available symbols please see:
     * https://unicode-org.github.io/icu/userguide/format_parse/datetime/#date-field-symbol-table
     *
     * @param int|float $time Hint: also supports DateTime, DateTimeInterface since 7.1.5
     * @return string
     */
    public function format($time, $pattern)
    {
        $result = $this->formatter($pattern)->format($time);
        if ($result === false) {
            throw new RuntimeException(sprintf(
                'Failed to format %s as "%s": %s (%d)',
                $time,
                $pattern,
                $this->formatter->getErrorMessage(),
                $this->formatter->getErrorCode()
            ));
        }

        return $result;
    }

    /**
     * @param $time
     * @return string
     */
    public function getFullDay($time)
    {
        return $this->dayFormatter->format($time);
    }

    /**
     * @param $time
     * @return string
     */
    public function getWeekOfYear($time)
    {
        return $this->format($time, 'ww');
    }

    /**
     * @param $time
     * @return int
     */
    public function getNumericWeekOfYear($time)
    {
        return (int) $this->format($time, 'w');
    }

    /**
     * @param $time
     * @return string
     */
    public function getDayInMonth($time)
    {
        return $this->format($time, 'dd');
    }

    /**
     * @param $time
     * @return int
     */
    public function getNumericDayInMonth($time)
    {
        return (int) $this->format($time, 'd');
    }

    /**
     * @param $time
     * @return string
     */
    public function getWeekdayName($time)
    {
        return $this->format($time, 'cccc');
    }

    /**
     * @param $time
     * @return string
     */
    public function getShortWeekdayName($time)
    {
        return $this->format($time, 'ccc');
    }

    /**
     * e.g. September
     *
     * @param $time
     * @return string
     */
    public function getMonthName($time)
    {
        return $this->format($time, 'LLLL');
    }

    /**
     * e.g. Sep
     *
     * @param $time
     * @return string
     */
    public function getShortMonthName($time)
    {
        return $this->format($time, 'LLL');
    }

    /**
     * e.g. 2021
     * @param $time
     * @return string
     */
    public function getYear($time)
    {
        return $this->format($time, 'y');
    }

    /**
     * e.g. 21
     *
     * @param $time
     * @return string
     */
    public function getShortYear($time)
    {
        return $this->format($time, 'yy');
    }

    /**
     * e.g. 21:50:12
     *
     * @param $time
     * @return string
     */
    public function getTime($time)
    {
        if ($this->wantsAmPm()) {
            return $this->format($time, 'h:mm:ss a');
        }

        return $this->format($time, 'H:mm:ss');
    }

    /**
     * e.g. 21:50
     *
     * @param $time
     * @return string
     */
    public function getShortTime($time)
    {
        if ($this->wantsAmPm()) {
            return $this->format($time, 'h:mm a');
        }

        return $this->format($time, 'H:mm');
    }

    protected function wantsAmPm()
    {
        // TODO: complete this list
        return in_array($this->getLocale(), ['en_US', 'en_US.UTF-8']);
    }

    protected function isUsEnglish()
    {
        return in_array($this->getLocale(), ['en_US', 'en_US.UTF-8']);
    }

    protected static function timeZonesAreEqual($left, $right)
    {
        if ($left instanceof DateTimeZone) {
            return $right instanceof DateTimeZone && $left->getName() === $right->getName();
        }
        if ($left instanceof IntlTimeZone) {
            return $right instanceof IntlTimeZone && $left->getID() === $right->getID();
        }

        throw new RuntimeException(sprintf(
            'Valid timezone expected, got %s',
            is_object($right) ? get_class($right) : gettype($right)
        ));
    }

    protected function formatter($pattern)
    {
        if ($this->formatter === null) {
            $this->formatter = new IntlDateFormatter(
                $this->getLocale(),
                IntlDateFormatter::GREGORIAN,
                IntlDateFormatter::GREGORIAN
            );
        }
        $this->formatter->setTimeZone($this->getTimezone());
        $this->formatter->setPattern($pattern);

        return $this->formatter;
    }

    protected function dayFormatter($pattern)
    {
        if ($this->dayFormatter === null) {
            $this->dayFormatter = new IntlDateFormatter(
                $this->getLocale(),
                IntlDateFormatter::FULL,
                IntlDateFormatter::NONE
            );
        }
        $this->dayFormatter->setTimeZone($this->getTimezone());
        $this->dayFormatter->setPattern($pattern);

        return $this->dayFormatter;
    }

    protected function getLocale()
    {
        if ($this->locale === null) {
            $this->locale = setlocale(LC_TIME, 0) ?: 'C';
        }

        return $this->locale;
    }

    protected function getTimezone()
    {
        if ($this->timezone === null) {
            $this->timezone = new DateTimeZone(date_default_timezone_get());
        }

        return $this->timezone;
    }

    protected function reset()
    {
        $this->formatter = null;
        $this->dayFormatter = null;
    }
}
