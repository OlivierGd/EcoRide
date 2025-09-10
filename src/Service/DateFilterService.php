<?php

namespace Olivierguissard\EcoRide\Service;

class DateFilterService
{
    /**
     * Retourne la plage de dates associée à un filtre prédéfini
     *
     * @param string $preset
     * @return array ['date_min' => 'YYYY-MM-DD', 'date_max' => 'YYYY-MM-DD']
     */
    public static function getDateRangeFromPreset(string $preset): array
    {
        $today = new \DateTimeImmutable('today');
        $start = $today;
        $end = $today;

        switch ($preset) {
            case 'today':
                break;

            case 'tomorrow':
                $start = $end = $today->modify('+1 day');
                break;

            case 'last_week':
                $start = $today->modify('-1 week')->modify('last monday');
                $end = $start->modify('+6 days');
                break;

            case 'this_week':
                $start = $today->modify('monday this week');
                $end = $start->modify('+6 days');
                break;

            case 'last_month':
                $start = $today->modify('first day of last month');
                $end = $today->modify('last day of last month');
                break;

            case 'this_month':
                $start = $today->modify('first day of this month');
                $end = $today->modify('last day of this month');
                break;

            default:
                // Aucun preset ou valeur non reconnue : on renvoie null
                return ['date_min' => null, 'date_max' => null];
        }

        return [
            'date_min' => $start->format('Y-m-d'),
            'date_max' => $end->format('Y-m-d')
        ];
    }
}

