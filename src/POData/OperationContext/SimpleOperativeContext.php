<?php

declare(strict_types=1);

namespace POData\OperationContext;

use POData\OperationContext\Web\OutgoingResponse;

/**
 * Class SimpleOperativeContext.
 * @package POData\OperationContext
 */
class SimpleOperativeContext implements IOperationContext
{
    /**
     * @var SimpleRequestAdapter;
     */
    protected $request;
    protected $response;

    /**
     * @param SimpleRequestAdapter $request
     */
    public function __construct($request)
    {
        $this->request  = new SimpleRequestAdapter($request);
        $this->response = new OutgoingResponse();
    }

    /**
     * Gets the Web request context for the request being sent.
     *
     * @return OutgoingResponse reference of OutgoingResponse object
     */
    public function outgoingResponse(): OutgoingResponse
    {
        return $this->response;
    }

    /**
     * Gets the Web request context for the request being received.
     *
     * @return IHTTPRequest reference of IncomingRequest object
     */
    public function incomingRequest(): IHTTPRequest
    {
        return $this->request;
    }
}
