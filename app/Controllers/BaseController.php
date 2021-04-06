<?php
namespace App\Controllers;

use CodeIgniter\Controller;
use CodeIgniter\API\ResponseTrait;

class BaseController extends Controller
{
	use ResponseTrait;
	
	protected $helpers = [];

	protected $session;
	protected $validation;

	protected $email;

	protected $development_mode;

	public function initController(\CodeIgniter\HTTP\RequestInterface $request, \CodeIgniter\HTTP\ResponseInterface $response, \Psr\Log\LoggerInterface $logger) {
		// Do Not Edit This Line
		parent::initController($request, $response, $logger);

		$this->session = \Config\Services::session();
		$this->validation = \Config\Services::validation();
		$this->email = \Config\Services::email();

		$this->development_mode = getenv('CI_ENVIRONMENT') == 'development';
	}

	public function options() {
		$this->setDefaultHeaders();
		return $this->response;
	}
	
	/* Sets the appropriate headers for CORS access. */
	protected function setDefaultHeaders() {
		try {
		$origin = $this->request->getHeader('Origin');
		if (!$origin)
			return;

		$origin = $origin->getValue();
		$allowed_origin = '';
		
		$main_domain = 'theatrebooking.com';
		if ($origin == ('https://' . $main_domain) || $origin == ('https://www.' . $main_domain))
			$allowed_origin = $origin;
			
		if ($this->development_mode)
			$allowed_origin = $origin;

		$this->response->setHeader('Access-Control-Allow-Origin', $allowed_origin);
		$this->response->setHeader('Access-Control-Allow-Headers', 'Authorization, Content-Type, *');
		$this->response->setHeader('Access-Control-Allow-Methods', 'POST, GET, OPTIONS, DELETE, PATCH, PUT');
		$this->response->setHeader('Access-Control-Allow-Credentials', 'true');
		} catch (Throwable $th) {
			// We do nothing. If the headers are not provided then access will be denied!
		}
	}

	/* Converts an array of strings to a single string. It uses pipe (|) as a separator. */
	protected function arrayToString($array) {
		$str = '';
		foreach ($array as $item) {
			$str .= $item . '|';
		}

		return \rtrim($str, '|');
	}

	/* Logs an exception. */
	protected function logException($exception, $extraMessage = '') {
		log_message('error', '[ERROR] {exception}', ['exception' => $exception]);
		
		if ($extraMessage)
			log_message('EXTRA MESSAGE: ' . $extraMessage);
	}

	/* Logs exceptions that are not expected to occcur. This method should take steps necessary to alert the site administrators ASAP. */
	protected function logUnexpectedException($message) {
		log_message('error', '[UNEXPECTED EXCEPTION] ' . $message);
	}

	/**
	 * Returns the absolute site url for the given file. The file must be in the public folder.
	 */
	protected function getAbsolutePublicFileUrl($file_path) {
		return \site_url('public/' . $file_path);
	}

	protected function sendEmail($subject, $body, $destination) {
		$this->email->setTo($destination);
		$this->email->setSubject($subject);
		$this->email->setMessage($body);

		$sent = $this->email->send();
		if (! $sent) {
			// Try once more
			$sent = $this->email->send();

			if (! $sent)
				$this->logUnexpectedException('Email not sent to "' . $destination . '". Subject: "' . $subject . '"');
		}

		return $sent;
	}
}
