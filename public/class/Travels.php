<?php

namespace class;

use DateTime;

class Travels
{
    private $suggestedStartCity;
    private $suggestedEndCity;
    private $proposalDate;
    private $proposalTime;
    private $places;
    private $priceRequested;
    private $commentForPassenger;
public function __construct(string $suggestedStartCity, string $suggestedEndCity, DateTime $proposalDate,
    string $proposalTime, int $places, int $priceRequested, string $commentForPassenger)
    {
        $this->suggestedStartCity = $suggestedStartCity;
        $this->suggestedEndCity = $suggestedEndCity;
        $this->proposalDate = $proposalDate;
        $this->proposalTime = $proposalTime;
        $this->places = $places;
        $this->priceRequested = $priceRequested;
        $this->commentForPassenger = $commentForPassenger;
    }
public function isValid(): bool
    {
        $now = new DateTime();
        return strlen($this->suggestedStartCity) > 2
            && strlen($this->suggestedEndCity) > 2
            && $this-> proposalDate > $now
            && ($this->places) > 0
            && ($this->priceRequested) > 0;
    }

    private function isValidTime(): bool
    {
        // Si la date est aujourd'hui, on vérifie que l'heure est dans le futur
        if ($this->proposalDate->format('Y-m-d') === date('Y-m-d')) {
            $currentTime = date('H:i');
            return $this->proposalTime > $currentTime;
        }
        return preg_match('/^([01][0-9]|2[0-3]):[0-5][0-9]$/', $this->proposalTime);
    }
}

