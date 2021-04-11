<?php namespace App\Controllers;

use CodeIgniter\I18n\Time;

class Admin extends BaseController
{
	protected $session_name = 'admin_session_data';
	
	public function __construct() {
		$this->model = \model('App\Models\AdminModel', true);
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

		$data = $this->model->getAdminData($username);

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

      $admin = $this->authenticateSession();

      if ($admin) {
        //unset($admin['id']);
				unset($admin['password']);
				
				$this->session->set($this->session_name, $admin);
        return $this->respond($admin);
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

	public function getBookings() {
		try {
      // Set the headers
			$this->setDefaultHeaders();
			
			$admin = $this->authenticateSession();

      if ($admin) {
				$bookings = $this->model->getBookings();
				if ($bookings) {
					return $this->respond($bookings);
				}
				else
					return $this->failNotFound('No booking yet.');
      }
      else {
				return $this->failUnauthorized('Authentication failed!');
			}
    } catch(\Throwable $th) {
			$this->logException($th);
			return $this->failServerError();
    }
	}

	public function addShow() {
		try {
      // Set the headers
			$this->setDefaultHeaders();
			
			$admin = $this->authenticateSession();

      if ($admin) {
				// Retrieve the sent data
				$data = $this->request->getPost();
				if (!$data)
					return $this->failNotFound('No valid data was provided!');

				$allowed_file_types = 'png,jpeg,jpg';
				$max_file_size      = 2048;

				// Validation rules
				$validationRules = [
					'name'        => 'trim|required|alpha_numeric_space|max_length[300]|is_unique[shows.name]',
					'description' => 'trim|required|alpha_numeric_punct|max_length[1000]',
					'image'       => "uploaded[image]|max_size[image,{$max_file_size}]|ext_in[image,{$allowed_file_types}]",
					'start_date'  => 'trim|required|valid_date[Y-m-d]',
					'end_date'    => 'trim|required|valid_date[Y-m-d]',
				];

				// Validate the data
				$this->validation->setRules($validationRules);
				if ($this->validation->run($data)) {
					// Validate the duration
					$start = new Time($data['start_date']); 
					$end = new Time($data['end_date']);
					if ($start->equals($end) || $start->isAfter($end) || $start->isBefore(new Time()))
						return $this->failValidationError('The duration is invalid. Please choose valid dates.');

					// First upload the image file

					// Get any uploaded avatar file
					$image = $this->request->getFile('image');

					helper('text');

					$dir = 'files/shows/';

					// Upload the new avatar
					$image_filename = \random_string('alnum', 30) . $admin['id'] . '.' . $image->getExtension();
					$entries['image'] = $image_filename;

					if (! $image->move($dir, $image_filename)) {
						// Failed to upload image
						return $this->fail('Could not upload the image.');
					}

					// Create the entries array
					$entries = [
						'name'        => $data['name'],
						'description' => $data['description'],
						'image'       => $image_filename,
						'start_date'  => $data['start_date'],
						'end_date'    => $data['end_date'],
					];

					if ($this->model->addShow($entries))
						return $this->respondCreated('Show added.');
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

	public function getShows() {
		try {
      // Set the headers
			$this->setDefaultHeaders();
			
			$admin = $this->authenticateSession();

      if ($admin) {
				$shows = $this->model->getShows();
				if ($shows) {
					$data = [];
					foreach($shows as $show) {
						$show['image_url'] = $this->getAbsolutePublicFileUrl('files/shows/' . $show['image']);
						$data[] = $show;
					}

					return $this->respond($data);
				}
				else
					return $this->failNotFound('No shows yet.');
      }
      else {
				return $this->failUnauthorized('Authentication failed!');
			}
    } catch(\Throwable $th) {
			$this->logException($th);
			return $this->failServerError();
    }
	}

	public function getPublicShows() {
		try {
      // Set the headers
			$this->setDefaultHeaders();
			
			$shows = $this->model->getShows();
			if ($shows) {
				$data = [];
				foreach($shows as $show) {
					$show['image_url'] = $this->getAbsolutePublicFileUrl('files/shows/' . $show['image']);
					$data[] = $show;
				}

				return $this->respond($data);
			}
			else
				return $this->failNotFound('No shows yet.');
    } catch(\Throwable $th) {
			$this->logException($th);
			return $this->failServerError();
    }
	}

	public function getTickets() {
		try {
      // Set the headers
			$this->setDefaultHeaders();
			
			$admin = $this->authenticateSession();

      if ($admin) {
				$tickets = $this->model->getTickets();
				if ($tickets) {
					return $this->respond($tickets);
				}
				else
					return $this->failNotFound('No tickets yet.');
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