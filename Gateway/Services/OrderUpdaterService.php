<?php

use Magento\Sales\Api\OrderRepositoryInterface;

class OrderUpdaterService
{
    /**
     * @var OrderRepositoryInterface
     */
    private $orderRepository;

    public function __construct(
        OrderRepositoryInterface $orderRepository
    ) {
        $this->orderRepository = $orderRepository;
    }

    /**
     * Cancels an order and authorization transaction.
     *
     * @param string $incrementId
     * @return bool
     */
    public function execute(\Magento\Sales\Api\Data\OrderInterface $order, $customStatus): bool
    {
/*        if ($order === null) {
            return false;
        }
        $order->setStatus($customStatus);
        $this->orderRepository->save($order);
        return true;*/
    }
}
