<?php

namespace Paymee\Core\Controller;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\CsrfAwareActionInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\Request\InvalidRequestException;

/**
 * Base controller for Paymee webhook endpoints.
 * Implements CsrfAwareActionInterface to allow webhook POST requests
 * without CSRF token validation (required for external API callbacks).
 */
abstract class AbstractNotification extends Action implements CsrfAwareActionInterface
{
    /**
     * @param RequestInterface $request
     * @return InvalidRequestException|null
     */
    public function createCsrfValidationException(RequestInterface $request): ?InvalidRequestException
    {
        return null;
    }

    /**
     * @param RequestInterface $request
     * @return bool|null
     */
    public function validateForCsrf(RequestInterface $request): ?bool
    {
        return true;
    }
}
