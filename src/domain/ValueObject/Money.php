<?php

namespace App\Domain\ValueObject;

class Money
{
    
    public function __construct(
        private float $amount,
        private string $currency = 'EUR'
    ) {
        if ($amount < 0) {
            throw new \InvalidArgumentException("Le montant ne peut pas être négatif");
        }
    }

    public function getAmount(): float
    {
        return $this->amount;
    }

    public function getCurrency(): string
    {
        return $this->currency;
    }

    
    public function add(Money $other): Money
    {
        if ($this->currency !== $other->getCurrency()) {
            throw new \InvalidArgumentException("Impossible d'additionner des devises différentes");
        }
        return new Money($this->amount + $other->getAmount(), $this->currency);
    }

    public function subtract(Money $other): Money
    {
        if ($this->currency !== $other->getCurrency()) {
            throw new \InvalidArgumentException("Impossible de soustraire des devises différentes");
        }
        $result = $this->amount - $other->getAmount();
        if ($result < 0) {
            throw new \InvalidArgumentException("Le résultat ne peut pas être négatif");
        }
        return new Money($result, $this->currency);
    }

    public function multiply(float $multiplier): Money
    {
        if ($multiplier < 0) {
            throw new \InvalidArgumentException("Le multiplicateur ne peut pas être négatif");
        }
        return new Money($this->amount * $multiplier, $this->currency);
    }

    public function equals(Money $other): bool
    {
        return $this->currency === $other->getCurrency() 
            && abs($this->amount - $other->getAmount()) < 0.01; // Tolérance pour les comparaisons de float
    }

    public function isGreaterThan(Money $other): bool
    {
        if ($this->currency !== $other->getCurrency()) {
            throw new \InvalidArgumentException("Impossible de comparer des devises différentes");
        }
        return $this->amount > $other->getAmount();
    }

    public function isLessThan(Money $other): bool
    {
        if ($this->currency !== $other->getCurrency()) {
            throw new \InvalidArgumentException("Impossible de comparer des devises différentes");
        }
        return $this->amount < $other->getAmount();
    }

    public function __toString(): string
    {
        return number_format($this->amount, 2, ',', ' ') . ' ' . $this->currency;
    }
}