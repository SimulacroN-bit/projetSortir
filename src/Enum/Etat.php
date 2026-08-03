<?php

namespace App\Enum;

enum Etat : string
{
    case EnCreation = 'En création';
    case Ouverte = 'Ouverte';
    case Cloturee = 'Cloturée';
    case EnCours = 'En cours';
    case Terminee = 'Terminée';
    case Annulee = 'Annulée';
    case Historisee = 'Historisée';
}
