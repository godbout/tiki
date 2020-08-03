<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

class Math_Formula_Currency implements Math_Formula_Applicator
{
    private $amount;
    private $currency;
    private $rates;

    public function __construct($amount, $currency, $rates = [])
    {
        $this->amount = $amount;
        $this->currency = $currency;
        $this->rates = $rates;
    }

    /**
     * Initialize a currency object from a currency tracker field.
     *
     * @param $handler - currency field handler
     * @return Math_Formula_Currency
     */
    public static function fromCurrencyField($handler)
    {
        $data = $handler->getFieldData();
        $rates = TikiLib::lib('trk')->exchange_rates($handler->getOption('currencyTracker'), $data['date']);

        return new self($data['amount'], $data['currency'], $rates);
    }

    /**
     * Parse a string and return currency object.
     *
     * @param $currency - string
     * @return Math_Formula_Currency if string is a currency representation or the $currency param otherwise.
     */
    public static function tryFromString($currency)
    {
        if (preg_match("/^(-?\d+(\.\d+)?)([A-Z]{3})$/i", $currency, $m)) {
            $rates = TikiLib::lib('trk')->exchange_rates(null, null);

            return new self($m[1], $m[3], $rates);
        }

        return $currency;
    }

    public function clone($amount)
    {
        return new self($amount, $this->currency, $this->rates);
    }

    public function getAmount()
    {
        return $this->amount;
    }

    public function getCurrency()
    {
        return $this->currency;
    }

    public function __toString()
    {
        return $this->amount . $this->currency;
    }

    public function convertTo($another_currency)
    {
        if (isset($this->rates[$this->currency])) {
            $defaultAmount = (float)$this->amount / (float)$this->rates[$this->currency];
        } else {
            $defaultAmount = $this->amount;
        }
        if (isset($this->rates[$another_currency])) {
            $amount = (float)$defaultAmount * (float)$this->rates[$another_currency];
        } else {
            $amount = $defaultAmount;
        }

        return new self($amount, $another_currency, $this->rates);
    }

    public function add($another)
    {
        return $this->calculate($another, function ($amount1, $amount2) {
            return (float)$amount1 + (float)$amount2;
        });
    }

    public function sub($another)
    {
        return $this->calculate($another, function ($amount1, $amount2) {
            return (float)$amount1 - (float)$amount2;
        });
    }

    public function mul($another)
    {
        return $this->calculate($another, function ($amount1, $amount2) {
            return (float)$amount1 * (float)$amount2;
        });
    }

    public function div($another)
    {
        return $this->calculate($another, function ($amount1, $amount2) {
            if ($amount2 != 0) {
                return (float)$amount1 / (float)$amount2;
            }

            throw new Math_Formula_Exception(tr('Division by zero with currency calculation: %0', $this));

            return 0;
        });
    }

    public function floor()
    {
        return new self(floor($this->amount), $this->currency, $this->rates);
    }

    public function ceil()
    {
        return new self(ceil($this->amount), $this->currency, $this->rates);
    }

    public function round($decimals)
    {
        return new self(round($this->amount, $decimals), $this->currency, $this->rates);
    }

    public function lessThan($another)
    {
        $amount = $this->convertAnother($another);

        return (float)$this->amount < (float)$amount;
    }

    public function moreThan($another)
    {
        $amount = $this->convertAnother($another);

        return (float)$this->amount > (float)$amount;
    }

    private function calculate($another, $callback)
    {
        $amount = $this->convertAnother($another);
        $result = $callback($this->amount, $amount);

        return new self($result, $this->currency, $this->rates);
    }

    private function convertAnother($another)
    {
        if ($another instanceof self) {
            $amount = $another->convertTo($this->currency)->getAmount();
        } elseif (is_numeric($another)) {
            $amount = $another;
        } elseif (empty($another)) {
            $amount = 0;
        } else {
            throw new Math_Formula_Exception(tr('Currency calculation tried with unknown entity: %0', $another));
        }

        return $amount;
    }
}
