<?php

namespace Olivierguissard\EcoRide\Model;
use Olivierguissard\EcoRide\config\DateTime;

require_once 'Travels.php';

class SuggestTrip
{
    private $file;

    public function __construct(string $file)
    {
        $directory = dirname($file);
        if (!is_dir($directory)) {
            mkdir($directory, 0777, true);
        }
        if (!file_exists($file)) {
            touch($file);
        }
        $this->file = $file;
    }

    public function addVoyage(Travels $voyage): void
    {
        file_put_contents($this->file, $voyage->toJSON() . PHP_EOL, FILE_APPEND);
    }

    public function getVoyages(): array
    {
        $content = trim(file_get_contents($this->file));
        $voyages = [];
        foreach (explode(PHP_EOL, $content) as $line) {
            $data = json_decode($line, true);
            $voyages[] = new Travels($data['suggestedStartCity'], $data['suggestedEndCity'], new DateTime($data['proposalDate']), $data['proposalTime'], $data['places'], $data['priceRequested'], $data['commentForPassenger']);
        }
        return array_reverse($voyages);
    }
}