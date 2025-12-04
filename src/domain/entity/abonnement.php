<?php

namespace App\Domain\Entity;

use DateTimeImmutable;

class Abonnement
{
    private ?int $id;
    private User $user;
    private Parking $parking;
    private DateTimeImmutable $dateDebut;
    private DateTimeImmutable $dateFin;
    /** @var array<string, array<string, string>> $creneauxHebdomadaires (Ex: ['Lundi' => ['18:00', '08:00'], 'Samedi' => ['00:00', '23:59']]) */
    private array $creneauxHebdomadaires;
    private float $montant;

    /**
     * @param User $user L'utilisateur abonné.
     * @param Parking $parking Le parking concerné.
     * @param DateTimeImmutable $dateDebut Date de début de l'abonnement.
     * @param DateTimeImmutable $dateFin Date de fin de l'abonnement.
     * @param array $creneauxHebdomadaires Le ou les créneaux horaires réservés par semaine.
     * @param float $montant Le prix mensuel ou total de l'abonnement.
     * @param int|null $id L'ID de l'abonnement.
     */
    public function __construct(
        User $user,
        Parking $parking,
        DateTimeImmutable $dateDebut,
        DateTimeImmutable $dateFin,
        array $creneauxHebdomadaires,
        float $montant,
        ?int $id = null
    ) {
        $this->id = $id;
        $this->user = $user;
        $this->parking = $parking;
        $this->dateDebut = $dateDebut;
        $this->dateFin = $dateFin;
        $this->creneauxHebdomadaires = $creneauxHebdomadaires;
        $this->montant = $montant;
    }

    // Méthodes Getters
    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function getParking(): Parking
    {
        return $this->parking;
    }

    public function getDateDebut(): DateTimeImmutable
    {
        return $this->dateDebut;
    }

    public function getDateFin(): DateTimeImmutable
    {
        return $this->dateFin;
    }

    public function getCreneauxHebdomadaires(): array
    {
        return $this->creneauxHebdomadaires;
    }

    public function getMontant(): float
    {
        return $this->montant;
    }

    // Exemple de logique métier
    public function isCurrentlyActive(): bool
    {
        $now = new DateTimeImmutable();
        return $now >= $this->dateDebut && $now <= $this->dateFin;
    }
}