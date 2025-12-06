<?php

namespace Domain\ValueObject;

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
            throw new \Exception("Impossible d'additionner des devises différentes");
        }
        return new Money($this->amount + $other->getAmount(), $this->currency);
    }
}