<?php

namespace class;

use DateTime;
use DateTimeZone;

class Travels
{
    const LIMIT_CARACTERES = 2;
    private string $suggestedStartCity;
    private string $suggestedEndCity;
    private DateTime $proposalDate;
    private string $proposalTime;
    private int $places;
    private int $priceRequested;
    private string $commentForPassenger;
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
        return strlen($this->suggestedStartCity) > self::LIMIT_CARACTERES
            && strlen($this->suggestedEndCity) > self::LIMIT_CARACTERES
            && $this->proposalDate > $now
            && ($this->places) > 0
            && ($this->priceRequested) > 0;
    }

    public function isValidTime(): bool
    {
        // Si la date est aujourd'hui, on vérifie que l'heure est dans le futur
        if ($this->proposalDate->format('Y-m-d') === date('Y-m-d')) {
            $currentTime = date('H:i');
            return $this->proposalTime > $currentTime;
        }
        return preg_match('/^([01][0-9]|2[0-3]):[0-5][0-9]$/', $this->proposalTime);
    }

    public function toHTML(): string
    {
        $startCity = htmlentities($this->suggestedStartCity);
        $this->proposalDate->setTimezone(new DateTimeZone('Europe/Paris'));
        $date = $this->proposalDate->format('d/m/Y à H:i');
        return <<<HTML
        <p><strong>{$startCity}</strong> <em>le {$date}</em></br>
        Le voyage
        </p>
HTML;

    }
    public function toJSON(): string
    {
        return json_encode([
            'suggestedStartCity'=> $this->suggestedStartCity,
            'suggestedEndCity'=> $this->suggestedEndCity,
            'proposalDate'=> $this->proposalDate->format('Y-m-d'),
            'proposalTime'=> $this->proposalTime,
            'places'=> $this->places,
            'priceRequested'=> $this->priceRequested,
            'commentForPassenger'=> $this->commentForPassenger,
        ]);
    }

    public function  getErrors(): array
    {
        $errors = [];
        if (($this->suggestedStartCity) === '') {
            $errors['suggestedStartCity'] = "La ville de départ n'est pas définie.";
        }
        if (($this->suggestedEndCity) === '') {
            $errors['suggestedEndCity'] = "La ville d'arrivée n'est pas définie.";
        }
        if (($this->proposalDate) === '') {
            $errors['proposalDate'] = "La date du voyage doit être indiquée au format jj/mm/aaaa.";
        }
        if (($this->proposalTime) === '') {
            $errors['proposalTime'] = "L'heure du voyage doit être indiquée au format hh:mm";
        }
        return $errors;
    }
}

