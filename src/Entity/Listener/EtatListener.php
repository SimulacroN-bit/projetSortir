<?php

namespace App\Entity\Listener;

use App\Entity\Sortie;
use App\Enum\Etat;
use DateTimeImmutable;

class EtatListener
{
    public function __construct(){

    }
    public function postLoad(Sortie $sortie): void
    {
        $sortie->setEtatAffichage($this->calculerEtatAffichage($sortie));
    }

    private function calculerEtatAffichage(Sortie $sortie): Etat
    {
        $etat = $sortie->getEtat();

        //États jamais recalculés en fonction du temps
        if (in_array($etat, [Etat::EnCreation, Etat::Annulee], true)) {
            return $etat;
        }

        $debut = $sortie->getDateHeureDebut();
        $duree = $sortie->getDuree();

        if (!$debut || !$duree) {
            return $etat;
        }

        $fin = $debut->modify('+' . $duree . ' minute');
        $maintenant = new DateTimeImmutable();

        //plus d'un mois après la fin réelle de la sortie, celle-ci est historisée et non consultable
        $seuilHistorisation = $fin->modify( '+1 month');
        if ($maintenant >= $seuilHistorisation) {
            return Etat::Historisee;
        }

        if ($maintenant >= $fin) {
            return Etat::Terminee;
        }

        if ($maintenant >= $debut) {
            return Etat::EnCours;
        }

        //vérifie si les inscriptions doivent être clôturées
        $maxPlacesAtteintes = $sortie->getParticipants()->count() >= $sortie->getNbInscriptionMax();
        $dateLimiteDepassee = $sortie->getDateLimiteInscription() && $maintenant >= $sortie->getDateLimiteInscription();

        if ($maxPlacesAtteintes || $dateLimiteDepassee) {
            return Etat::Cloturee;
        }

        // reste Ouverte
        return $etat;
    }
}