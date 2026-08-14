<?php

namespace App\Security\Voter;

use App\Entity\Participant;
use App\Entity\Sortie;
use App\Enum\Etat;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

//centralisation de la règle d'autorisation qui sera utilisé à la fois dans SortieController et
//dans user/index.html.twig
final class SortieVoter extends Voter
{
    public const UPDATE = 'SORTIE_UPDATE';
    public const CANCEL = 'SORTIE_CANCEL';

    protected function supports(string $attribute, mixed $subject): bool
    {
        // Couvre modifier / publier / supprimer
        return in_array($attribute, [self::UPDATE, self::CANCEL], true)
            && $subject instanceof Sortie;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        $participant = $token->getUser();

        //vérifie si 'utilisateur est connecté
        if (!$participant instanceof Participant) {
            return false;
        }

        /** @var Sortie $sortie */
        $sortie = $subject;

        //vérifie si 'utilisateur est l'organisateur
        if ($sortie->getOrganisateur() !== $participant) {
            return false;
        }

        return match ($attribute) {
            self::UPDATE => $sortie->getEtatAffichage() === Etat::EnCreation,
            self::CANCEL => in_array($sortie->getEtatAffichage(), [Etat::Ouverte, Etat::Cloturee], true),
            default => false,
        };
    }
}
