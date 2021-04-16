<?php namespace App\Controllers;

class User extends BaseController
{
	protected $session_name = 'user_session_data';
	
	public function __construct() {
		$this->model = \model('App\Models\UserModel', true);
	}
  
  private function authenticate() {
		if (! $this->request->hasHeader('Authorization'))
			return FALSE;

		$credentials = $this->request->getHeader('Authorization')->getValue();
		$credentials = \explode(' ', $credentials);

		if (\count($credentials) < 2)
			return FALSE;

		if ($credentials[0] !== 'Basic')
			return FALSE;
		
		$credentials = $credentials[1];
		$credentials = \base64_decode($credentials, TRUE);

		if (! $credentials)
			return FALSE;

		$credentials = \explode(':', $credentials);

		if (\count($credentials) < 2)
			return FALSE;

		$username = $credentials[0];
		$password = $credentials[1];

		$data = $this->model->getUserData($username);

		if (! $data)
			return FALSE;

		if (password_verify($password, $data['password'])) {
			return $data;
		}
		else
			return FALSE;
	}

	private function authenticateSession() {
    // Check if a valid session exists
    if ($this->session->has($this->session_name)) {
			$data = $this->session->get($this->session_name);
			return $data;
		}
    else {
      // No session data found. Let's try other authentication method(s)
      // We can authenticate if a BASIC AUTH header was provided.
      return $this->authenticate();
		}
	}

	public function createSession() {
		try {
      // Set the headers
      $this->setDefaultHeaders();

      $user = $this->authenticateSession();

      if ($user) {
				unset($user['password']);
				
				$this->session->set($this->session_name, $user);
        return $this->respond($user);
      }
      else {
				return $this->failUnauthorized('Authentication failed!');
			}
    } catch(\Throwable $th) {
			$this->logException($th);
			return $this->failServerError();
    }
	}
	
	public function clearSession() {
		try {
			// Set the headers
			$this->setDefaultHeaders();

			// Clear the current session
			$this->session->destroy();
			return $this->respondDeleted('Session cleared');
		} catch(\Throwable $th) {
			$this->logException($th);
			return $this->failServerError();
		}
	}

	public function createUser() {
		try {
      // Set the headers
			$this->setDefaultHeaders();
			
			// Retrieve the sent data
			$data = $this->request->getJSON(true);
			if (!$data)
				return $this->failNotFound('No valid data was provided!');

			// Validation rules
			$validationRules = [
				'name'     => 'trim|required|alpha_numeric_space|max_length[30]',
				'username' => 'trim|required|max_length[12]|is_unique[users.username]',
				'password' => 'required',
			];

			// Validate the data
			$this->validation->setRules($validationRules);
			if ($this->validation->run($data)) {
				$password = \password_hash($data['password'], PASSWORD_DEFAULT);

				$entries = [
					'name'     => $data['name'],
					'username' => $data['username'],
					'password' => $password,
				];
				
				// Insert it to the database
				if ($this->model->createUser($entries)) {
					return $this->respond(['message' => 'User account created.']);
				}
				else {
					return $this->failServerError();
				}
			}
			else {
				return $this->failValidationError($this->arrayToString($this->validation->getErrors()));
			}
    } catch(\Throwable $th) {
			$this->logException($th);
			return $this->failServerError();
    }
	}
	
  public function addBooking() {
		try {
      // Set the headers
			$this->setDefaultHeaders();
			
			$user = $this->authenticateSession();

      if ($user) {
				// Retrieve the sent data
				$data = $this->request->getJSON(true);
				if (!$data)
					return $this->failNotFound('No valid data was provided!');

				// Validation rules
				$validationRules = [
					'show_id'  => 'trim|required|numeric|is_not_unique[shows.id]',
				];

				// Validate the data
				$this->validation->setRules($validationRules);
				if ($this->validation->run($data)) {
					// Make sure we don't have upto 30 tickets sold for this show
					if ($this->model->getTotalTickets($data['show_id']) >= 30)
						return $this->failResourceExists('This show cannot booked anymore');

					$ticket_id = $this->model->addTicket(['show_id' => $data['show_id']]);
					if (!$ticket_id)
						return $this->fail('Failed to add show.');

					// Cend_datereate the entries array
					$entries = [
						'location'  => '',
						'user_id'   => $user['id'],
						'ticket_id' => $ticket_id,
					];

					if ($this->model->addBooking($entries))
						return $this->respondCreated('Show Booked.');
					else
						return $this->fail('Failed to add show.');
				}
				else {
					return $this->failValidationError($this->arrayToString($this->validation->getErrors()));
				}
      }
      else {
				return $this->failUnauthorized('Authentication failed!');
			}
    } catch(\Throwable $th) {
			$this->logException($th);
			return $this->failServerError();
    }
	}
}