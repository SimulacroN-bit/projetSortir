<?php

namespace App\Entity;

use App\Entity\Listener\EtatListener;
use App\Enum\Etat;
use App\Repository\SortieRepository;
use DateTimeImmutable;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: SortieRepository::class)]
#[ORM\EntityListeners([EtatListener::class])]
class Sortie
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 180)]
    #[Assert\NotBlank(message: 'Le nom de la sortie est obligatoire.')]
    #[Assert\Length(
        min: 3,
        max: 180,
        maxMessage: 'Le nom de la sortie ne peut pas dépasser {{ limit }} caractères.'
    )]
    private ?string $nom = null;

    #[ORM\Column]
    #[Assert\NotNull(message: 'La date et l\'heure de la sortie sont obligatoires.')]
    private ?DateTimeImmutable $dateHeureDebut = null;

    #[ORM\Column]
    #[Assert\NotNull(message: 'La durée de la sortie est obligatoires.')]
    #[Assert\Positive(message: 'La durée de la sortie doit être positive.')]
    private ?int $duree = null;

    #[ORM\Column]
    #[Assert\NotNull(message: 'La date limite d\'inscription à la sortie est obligatoire.')]
    private ?DateTimeImmutable $dateLimiteInscription = null;

    #[ORM\Column]
    #[Assert\NotNull(message: 'Le nombre de places est obligatoire.')]
    #[Assert\Positive(message: 'Le nombre de places doit être positif.')]
    private ?int $nbInscriptionMax = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Assert\Length(
        min: 3,
        max: 255,
        maxMessage: 'La description ne peut pas dépasser {{ limit }} caractères.'
    )]
    private ?string $infosSortie = null;

    #[ORM\Column(enumType: Etat::class)]
    private ?Etat $etat = null;

    //Propriété non mappée : calculée dynamiquement par EtatListener, jamais écrite en base de données
    private ?Etat $etatAffichage = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Assert\NotBlank(message: 'Le motif d\'annulation est obligatoire.', groups: ['annulation'])]
    private ?string $motifAnnulation = null;

    #[ORM\ManyToOne(inversedBy: 'sorties')]
    #[ORM\JoinColumn(nullable: false)]
    #[Assert\NotNull(message: 'Le lieu est obligatoire.')]
    private ?Lieu $lieu = null;

    /**
     * @var Collection<int, Participant>
     */
    #[ORM\ManyToMany(targetEntity: Participant::class, inversedBy: 'sorties')]
    private Collection $participants;

    #[ORM\ManyToOne(inversedBy: 'sortiesOrganisees')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Participant $organisateur = null;
    #[ORM\ManyToOne(inversedBy: 'sorties')]
    #[ORM\JoinColumn(nullable: false)]
    #[Assert\NotNull(message: 'Le campus est obligatoire.')]
    private ?Campus $campus = null;

    public function __construct()
    {
        $this->participants = new ArrayCollection();
    }


    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNom(): ?string
    {
        return $this->nom;
    }

    public function setNom(string $nom): static
    {
        $this->nom = $nom;

        return $this;
    }

    public function getDateHeureDebut(): ?\DateTimeImmutable
    {
        return $this->dateHeureDebut;
    }

    public function setDateHeureDebut(\DateTimeImmutable $dateHeureDebut): static
    {
        $this->dateHeureDebut = $dateHeureDebut;

        return $this;
    }

    public function getDuree(): ?int
    {
        return $this->duree;
    }

    public function setDuree(int $duree): static
    {
        $this->duree = $duree;

        return $this;
    }

    public function getDateLimiteInscription(): ?DateTimeImmutable
    {
        return $this->dateLimiteInscription;
    }

    public function setDateLimiteInscription(DateTimeImmutable $dateLimiteInscription): static
    {
        $this->dateLimiteInscription = $dateLimiteInscription;

        return $this;
    }

    public function getNbInscriptionMax(): ?int
    {
        return $this->nbInscriptionMax;
    }

    public function setNbInscriptionMax(int $nbInscriptionMax): static
    {
        $this->nbInscriptionMax = $nbInscriptionMax;

        return $this;
    }

    public function getInfosSortie(): ?string
    {
        return $this->infosSortie;
    }

    public function setInfosSortie(?string $infosSortie): static
    {
        $this->infosSortie = $infosSortie;

        return $this;
    }

    /**
     * @return Etat|null
     */
    public function getEtat(): ?Etat
    {
        return $this->etat;
    }

    public function setEtat(Etat $etat): static
    {
        $this->etat = $etat;
        return $this;
    }

    public function getEtatAffichage(): ?Etat
    {
        //Fallback sur $etat pour une Sortie tout juste créée en mémoire
        //pas encore rechargée depuis la base de données (postLoad ne s'est pas encore déclenché)
        return $this->etatAffichage ?? $this->etat;
    }

    public function setEtatAffichage(?Etat $etatAffichage): static
    {
        $this->etatAffichage = $etatAffichage;
        return $this;
    }

    public function getMotifAnnulation(): ?string
    {
        return $this->motifAnnulation;
    }

    public function setMotifAnnulation(?string $motifAnnulation): static
    {
        $this->motifAnnulation = $motifAnnulation;
        return $this;
    }

    public function getLieu(): ?Lieu
    {
        return $this->lieu;
    }

    public function setLieu(?Lieu $lieu): static
    {
        $this->lieu = $lieu;

        return $this;
    }

    /**
     * @return Collection<int, Participant>
     */
    public function getParticipants(): Collection
    {
        return $this->participants;
    }

    public function addParticipant(Participant $participant): static
    {
        if (!$this->participants->contains($participant)) {
            $this->participants->add($participant);
        }

        return $this;
    }

    public function removeParticipant(Participant $participant): static
    {
        $this->participants->removeElement($participant);

        return $this;
    }

    public function getOrganisateur(): ?Participant
    {
        return $this->organisateur;
    }

    public function setOrganisateur(?Participant $organisateur): static
    {
        $this->organisateur = $organisateur;

        return $this;
    }

    public function getCampus(): ?Campus
    {
        return $this->campus;
    }

    public function setCampus(?Campus $campus): static
    {
        $this->campus = $campus;

        return $this;
    }
}
