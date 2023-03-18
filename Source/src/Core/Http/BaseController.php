<?php

namespace Api\Core\Http;

use Api\Core\Factories\ResponseFactory;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Log\LoggerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

abstract class BaseController implements ControllerInterface {

    protected LoggerInterface $log;
    protected ValidatorInterface $validator;

    public function __construct(LoggerInterface $log, ValidatorInterface $validator) {
        $this->log = $log;
        $this->validator = $validator;
    }

    public function handle(Request $request, Response $response, array $args = []) {
        $errors = $this->validate($request);
        if($errors) return ResponseFactory::MakeJSON($errors)->BadRequest();
        return $this->process($request, $response, $args);
    }

    public function validate(Request $request) : array {
        return [];
    }

    public function process(Request $request, Response $response, array $args = []): Response {
        return ResponseFactory::Make()->Success();
    }
}