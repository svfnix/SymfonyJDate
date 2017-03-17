<?php

namespace Svfnix\JalaliDateBundle\Twig;

use Svfnix\JalaliDateBundle\lib\JalaliDateTime as JDate;

class JalaliDateExtension extends \Twig_Extension
{
    const DEFAULT_INTERVAL_FORMAT = '%d روز';
    const DEFAULT_DATE_FORMAT = 'l, d M Y H:i:s O';

    /**
     * Instance of JalaliDateTime service
     * @var JDate
     */
    private $jDate;

    public function __construct(JDate $jDate) {
        $this->jDate = $jDate;
    } 

    public function getFilters()
    {
        return array(
            new \Twig_SimpleFilter('jdate', [$this, 'jDateFilter'], ['needs_environment' => true]),
            new \Twig_SimpleFilter('time_ago', [$this, 'timeAgo']),
        );
    }

    public function jDateFilter($env, $date, $format = null, $timezone = null) {
        if (null === $format) {
            $format = $date instanceof \DateInterval ? self::DEFAULT_INTERVAL_FORMAT : self::DEFAULT_DATE_FORMAT;
        }

        if ($date instanceof \DateInterval) {
            return $date->format($format);
        }

        return $this->jDateConverter($env, $date, $format, $timezone);
    }

    public function jDateConverter($env, $date, $format, $timezone) {
        if (false !== $timezone) {
            if (null === $timezone) {
                $timezone = new \DateTimeZone(date_default_timezone_get());
            } elseif (!$timezone instanceof \DateTimeZone) {
                $timezone = new \DateTimeZone($timezone);
            }
        }

        if ($date instanceof \DateTimeImmutable) {
            if (false !== $timezone) {
                $date->setTimezone($timezone);
            }
            return $this->jDate->date($format, $date->getTimestamp(), true, true);
        }

        if ($date instanceof \DateTime || $date instanceof \DateTimeInterface) {
            $date = clone $date;
            if (false !== $timezone) {
                $date->setTimezone($timezone);
            }

            return $this->jDate->date($format, $date->getTimestamp(), true, true);
        }

        $asString = (string) $date;
        if (ctype_digit($asString) || (!empty($asString) && '-' === $asString[0] && ctype_digit(substr($asString, 1)))) {
            $date = '@'.$date;
        }

        $date = new \DateTime($date, $env->getExtension('core')->getTimezone());
        if (false !== $timezone) {
            $date->setTimezone($timezone);
        }

        return $this->jDate->date($format, $date->getTimestamp(), true, true);
    }

    function timeAgo($time_ago, $format = null, $timezone = null)
    {

        if($time_ago instanceof \DateTime){
            $time_ago = $time_ago->format('Y-m-d H:i:s');
        }

        $time_ago       = strtotime($time_ago);
        $cur_time       = time();
        $time_elapsed   = $cur_time - $time_ago;
        $minutes        = round($time_elapsed / 60 );
        $hours          = round($time_elapsed / 3600);
        $days           = round($time_elapsed / 86400 );

        if($days > 2){
            return $this->convertNumbers($this->jDateFilter($time_ago, $format, $timezone));
        } else if ($days > 1){
            return 'دیروز';
        } else if($hours) {
            return $this->convertNumbers("{$hours} ساعت پیش");
        } else if($minutes) {
            return $this->convertNumbers("{$minutes} دقیقه پیش");
        } else {
            return 'لحظاتی پیش';
        }
    }


    /**
     * Converts latin numbers to farsi script
     * @param string $matches
     * @return mixed
     */
    private static function convertNumbers($matches)
    {
        $farsi_array   = array('۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹');
        $english_array = array('0', '1', '2', '3', '4', '5', '6', '7', '8', '9');

        return str_replace($english_array, $farsi_array, $matches);
    }

    public function getName() {
        return 'symfony_persia.jdate_extension';
    }
}
