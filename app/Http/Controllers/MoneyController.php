<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class MoneyController extends Controller {


    public function __construct() {
        // public $currency;
        $this->middleware('auth:api');
        $this->currency = [
            "EUR", "USD", "JPY", "BGN", "CZK", "DKK", "GBP", "HUF", "PLN", "RON", "SEK",
            "CHF", "ISK", "NOK", "HRK", "TRY", "AUD", "BRL", "CAD", "CNY", "HKD", "IDR",
            "ILS", "INR", "KRW", "MXN", "MYR", "NZD", "PHP", "SGD", "THB", "ZAR",
        ];
    }
    /**
     * API for periods like Year, Month, Week and Day.
     *
     * @param Request $request date
     *   Date contains periods like Year, Month, Week and Day.
     *
     * @return \Illuminate\Http\JsonResponse
     *   Response with result data.
     */
    public function period(Request $request) {
        $this->validate($request, [
            'date' => 'required',
        ]);
        $base = 'USD';
        $date = $request['date'];
        if (in_array($request['base'], $this->currency)) {
            $base = $request['base'];
        }
        $count = strlen($date);
        switch ($count) {
            case 10:
                if (!preg_match("/^(19|20)\d{2}-(0[1-9]|1[0-2])-(0[1-9]|[1-2][0-9]|3[0-1])$/", $date)) {
                    return response()->json(['message' => 'Please correct the date value in YYYY-MM-DD format and ranging the year between 1900 to 2099.']);
                }
                $previousDay = date('Y-m-d', strtotime('-1 day', strtotime($date)));
                $result = $this->currencyData($previousDay, $date, $base);
                break;
            case 8:
                if (!preg_match("/^^(19|20)\d{2}-W+(5[0-3]|[1-4][0-9]|[1-9])$/", $date)) {
                    return response()->json(['message' => 'Please correct the date value in YYYY-Www format and ranging the year between 1900 to 2099, week 01 to 53.']);
                }
                $data = explode('-', $date);
                $current = date('W');
                $year = $data[0];
                $week = str_replace('W', '', $data[1]);
                $start = date("Y-m-d", strtotime($year . 'W' . str_pad($week, 2, 0, STR_PAD_LEFT)));
                $end = date("Y-m-d", strtotime($year . 'W' . str_pad($week - 1, 2, 0, STR_PAD_LEFT) . ' +4 days'));
                if ($week > $current) {
                    return response()->json(['message' => 'Please enter the past week.']);
                } else {
                    $result = $this->currencyData($start, $end, $base);
                }
                break;
            case 7:
                if (!preg_match("/^(19|20)\d{2}-(0[1-9]|1[0-2])$/", $date)) {
                    return response()->json(['message' => 'Please correct the date value in YYYY-MM format and ranging the year between 1900 to 2099.']);
                }
                $data = explode('-', $date);
                $year = $data[0];
                $month = $data[1];
                $day = cal_days_in_month(CAL_GREGORIAN, $month, $year);
                $start = $this->getDay($year . "-" . $month . "-01", 'Add');
                $end = $this->getDay($year . "-" . ($month - 1) . "-" . $day);
                $result = $this->currencyData($start, $end, $base);
                break;
            case 4:
                if (!preg_match("/^(19|20)\d{2}$/", $date)) {
                    return response()->json(['message' => 'Please correct the date value in YYYY format and ranging the year between 1900 to 2099.']);
                }
                $year = date("Y");
                if ($date == $year) {
                    $start = $this->getDay($year . "-01-02", 'Add');
                    $end = $this->getDay(($year - 1) . "-12-31");
                } elseif ($date < $year) {
                    $start = $this->getDay($date . "-01-02", 'Add');
                    $end = $this->getDay(($date - 1) . "-12-31");
                }
                if ($date > $year && $date != $year) {
                    return response()->json(['message' => 'Please enter current or the past years.']);
                } else {
                    $result = $this->currencyData($start, $end, $base);
                }
                break;
            default:
                return response()->json(['message' => 'Please enter the correct date value.']);
                break;
        }
        return response()->json($result);
    }


    /**
     * API for range with start_date and end_date.
     *
     * @param Request $request
     *   Start_date, end_date.
     *
     * @return \Illuminate\Http\JsonResponse
     *   Response with result data.
     */
    public function range(Request $request) {
        $this->validate($request, [
            'start_date' => 'required|date_format:Y-m-d',
            'end_date' => 'required|date_format:Y-m-d',
        ]);
        $base = 'USD';
        $start = $this->getDay($request['start_date'], 'Add');
        $end = $this->getDay($request['end_date']);
        if (in_array($request['base'], $this->currency)) {
            $base = $request['base'];
        }
        if ($start > $end) {
            return response()->json(['message' => 'End date should be greater then start date.']);
        } elseif ($start == $end) {
            return response()->json(['message' => 'Start date and end date should not be same.']);
        } else {
            $result = $this->currencyData($start, $end, $base);
            return response()->json($result);
        }
    }

    /**
     * Getting the API data and mkae the array for caclulation.
     *
     * @param string $start
     *   Start date.
     * @param string $end
     *   End date.
     *
     * @return array
     *   Response with result data.
     */
    protected function currencyData($start, $end, $base) {
        $startData = $this->currencyapi($start, $base);
        $endData = $this->currencyapi($end, $base);
        $startRates = (array)$startData->rates;
        $endRates = (array)$endData->rates;
        $result = $this->calculate($startRates, $endRates);
        return $result;
    }

    /**
     * Get the array data contains percentages.
     *
     * @param array $data1
     *   $startDate data.
     * @param array $data2
     *   $endDate data.
     *
     * @return array
     *   Response with result data.
     */
    protected function calculate($data1, $data2) {
        $result = [];
        $currency = array_keys($data1);
        foreach ($currency as $val) {
            $oldFigure = !empty($data1[$val]) ? round($data1[$val], 5) : 0;
            $newFigure = !empty($data2[$val]) ? round($data2[$val], 5) : 0;
            $result[$val] = $this->calculatePercent($oldFigure, $newFigure);
        }
        return $result;
    }

    /**
     * Get working day date excluding sat and sun.
     *
     * @param string $date
     *   Date.
     * @param string $day
     *   Add or remove days.
     *
     * @return string
     *   Response with result date.
     */
    protected function getDay($date, $day = NULL) {
        $weekday = date('D', strtotime($date));
        switch($weekday) {
            case 'Sun':
                if ($day == 'Add') {
                    $date = date('Y-m-d', strtotime('+1 day', strtotime($date)));
                } else {
                    $date = date('Y-m-d', strtotime('-2 days', strtotime($date)));
                }
                break;
            case 'Sat':
                if ($day == 'Add') {
                    $date = date('Y-m-d', strtotime('+2 days', strtotime($date)));
                } else {
                    $date = date('Y-m-d', strtotime('-1 day', strtotime($date)));
                }
                break;
        }
        return $date;
    }

    /**
     * Get the percent for given two numbers.
     *
     * @param float $oldFigure
     *   Start date data.
     * @param float $newFigure
     *   End date data.
     *
     * @return string
     *   Response with result data.
     */
    protected function calculatePercent($oldFigure, $newFigure) {
        $percentChange = round((($oldFigure - $newFigure) / $oldFigure) * 100, 2);
        return $percentChange . '%';
    }


    /**
     * The API call to get the rates.
     *
     * @param string $date
     *   Date for which fetching the data.
     *
     * @return array
     *   Response with result data.
     */
    protected function currencyapi($date, $base) {
        $url = 'https://api.vatcomply.com/rates?';
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url . 'date=' . $date . '&base=' . $base);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, TRUE);
        $result = json_decode(curl_exec($curl));
        curl_close($curl);
        return $result;
    }

}
