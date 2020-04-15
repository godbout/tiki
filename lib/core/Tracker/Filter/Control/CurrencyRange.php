<?php
// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

namespace Tracker\Filter\Control;

class CurrencyRange implements Control
{
	private $fieldName;
	private $from = '';
	private $fromCurrency = '';
	private $to = '';
	private $toCurrency = '';
	private $meta = 0;

	function __construct($name, $meta)
	{
		$this->fieldName = $name;
		$this->meta = $meta;
	}

	function applyInput(\JitFilter $input)
	{
		$this->from = $input->{$this->fieldName . '_from'}->float() ?: '';
		$this->fromCurrency = $input->{$this->fieldName . '_from_currency'}->text() ?: '';
		$this->to = $input->{$this->fieldName . '_to'}->float() ?: '';
		$this->toCurrency = $input->{$this->fieldName . '_to_currency'}->text() ?: '';
	}

	function getQueryArguments()
	{
		if ($this->from && $this->to) {
			return [
				$this->fieldName . '_from' => $this->from,
				$this->fieldName . '_from_currency' => $this->fromCurrency,
				$this->fieldName . '_to' => $this->to,
				$this->fieldName . '_to_currency' => $this->toCurrency,
			];
		} else {
			return [];
		}
	}

	function getDescription()
	{
		if ($this->hasValue()) {
			$tikilib = \TikiLib::lib('tiki');
			return tr(
				'From %0 to %1',
				$this->from.$this->fromCurrency,
				$this->to.$this->toCurrency
			);
		} else {
			return '';
		}
	}

	function getId()
	{
		return $this->fieldName . '_from';
	}

	function isUsable()
	{
		return true;
	}

	function hasValue()
	{
		return ! empty($this->from) && ! empty($this->to);
	}

	function getFrom()
	{
		return $this->from;
	}

	function getFromCurrency()
	{
		return $this->fromCurrency;
	}

	function getTo()
	{
		return $this->to;
	}

	function getToCurrency()
	{
		return $this->toCurrency;
	}

	function __toString()
	{
		$smarty = \TikiLib::lib('smarty');
		$smarty->assign('control', [
			'field' => $this->fieldName,
			'from' => $this->from,
			'fromCurrency' => $this->fromCurrency,
			'to' => $this->to,
			'toCurrency' => $this->toCurrency,
			'meta' => $this->meta,
		]);
		return $smarty->fetch('filter_control/currency_range.tpl');
	}
}
