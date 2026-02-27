<?php

namespace Arbory\Merchant\Utils;

interface Orderable
{
    /**
     * Get the item ID
     *
     * @return string
     */
    public function getId();

    /**
     * Get the item Class
     *
     * @return string
     */
    public function getClass();

    /**
     * Get the item price without vat
     */
    public function getPrice(): int;

    /**
     * Get the VAT tax value like (21.5% => 21.5)
     *
     * @return int
     */
    public function getVatRate(): float;

    /**
     * Get the item summary
     *
     * @return string
     */
    public function getSummary();

    /**
     * Get the item options
     */
    public function getOptions(): array;

    /**
     * Get the item quantity
     *
     * @return int
     */
    public function getQuantity();

    /**
     * Get the items total price without VAT
     */
    public function getTotal(): int;
}
