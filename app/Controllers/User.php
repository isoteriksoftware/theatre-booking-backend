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
				$data = $this->request->getPost();
				if (!$data)
					return $this->failNotFound('No valid data was provided!');

				$allowed_image_file_types = 'png,jpeg,jpg';
				$max_item_image_file_size      = \intval(getenv('MAX_ITEM_IMAGE_SIZE'));

				// Validation rules
				$validationRules = [
					'booking_option'       => 'trim|required|in_list[PICK_UP, DROP_OFF]',
          'receiver_name'        => 'trim|required|alpha_numeric_space|max_length[50]',
          'receiver_phone'       => 'trim|required|alpha_numeric|max_length[15]',
          'receiver_address'     => 'trim|required|alpha_numeric_punct|max_length[500]',
          'receiver_city_state'  => 'trim|required|alpha_numeric_punct|max_length[100]',
          'sender_name'          => 'trim|required|alpha_numeric_space|max_length[50]',
          'sender_phone'         => 'trim|required|alpha_numeric|max_length[15]',
          'sender_address'       => 'trim|required|alpha_numeric_punct|max_length[500]',
					'sender_city_state'    => 'trim|required|alpha_numeric_punct|max_length[100]',
					'items'                => 'required',
          'booking_type'         => 'trim|required|in_list[SAME_DAY, NEXT_DAY, EXPRESS, WAYBILL, BULK]',
					'distance_to_receiver' => 'trim|required|max_length[50]',
					'estimated_cost'       => 'trim|required|numeric|greater_than[0]',
				];

				if (isset($data['booking_notes']) && \trim($data['booking_notes']) != '')
					$validationRules['booking_notes'] = 'trim|alpha_numeric_punct|max_length[1000]';

				// Items image is optional
				$items_image_file = $this->request->getFile('items_image');
				if ($items_image_file)
					$validationRules['items_image'] = "uploaded[items_image]|max_size[items_image,{$max_item_image_file_size}]|ext_in[items_image,{$allowed_image_file_types}]";

				// Validate the data
				$this->validation->setRules($validationRules);
				if ($this->validation->run($data)) {
					// Get the items
					$items = \explode(',', $data['items']);

					// Set item validation rules
					$validationRules = [
						0 => 'trim|required|in_list[FOOD, ELECTRONICS, PHONES, OTHERS]',
						1 => 'trim|required|max_length[50]',
						2 => 'trim|required|strtolower|in_list[kg, lbs]',
					];
					$this->validation->setRules($validationRules);

					// Validate each item
					foreach ($items as $item) {
						if (!$this->validation->run(\explode('|', $item)))
							return $this->failValidationError($this->arrayToString($this->validation->getErrors()));
					}

					// Upload the items image if any
					$items_image_filename = '';
					if ($items_image_file) {
						helper('text');
						$items_images_dir = WRITEPATH . getenv('ITEMS_IMAGES_DIRECTORY');
						$items_image_filename = \random_string('alnum', 30) . $user['id'] . '.' . $items_image_file->getExtension();
						
						if (! $items_image_file->move($items_images_dir, $items_image_filename)) {
							// Failed to upload the image
							return $this->fail('Could not upload the items image.');
						}
					}

					// Add the items to the database
					$items_ids = [];
					foreach ($items as $item) {
						$values = \explode('|', $item);
						$entries = [
							'item_type'        => $values[0],
							'item_weight'      => $values[1],
							'item_weight_unit' => $values[2],
						];

						$insert_id = $this->model->addBookedItem($entries);

						if (!$insert_id)
							return $this->failServerError('Failed to add the items.');

						$items_ids[] = $insert_id;
					}

					$items_ids = \implode('|', $items_ids);

					// Create the entries array
					$entries = [
						'booking_id'          => $this->generateBookingID($user['id']),
						'booking_option'      => $data['booking_option'],
						'receiver_name'       => $data['receiver_name'],
						'receiver_phone'      => $data['receiver_phone'],
						'receiver_address'    => $data['receiver_address'],
						'receiver_city_state' => $data['receiver_city_state'],
						'sender_name'         => $data['sender_name'],
						'sender_phone'        => $data['sender_phone'],
						'sender_address'      => $data['sender_address'],
						'sender_city_state'   => $data['sender_city_state'],
						'items_ids'           => $items_ids,
						'booking_type'        => $data['booking_type'],
						'booking_notes'       => $data['booking_notes'] ? $data['booking_notes'] : '',
						'distance_to_receiver'=> $data['distance_to_receiver'],
						'estimated_cost'      => $data['estimated_cost'],
						'user_id'             => $user['id'],
					];

					if ($items_image_file)
						$entries['items_image'] = $items_image_filename;

					// If a transaction reference is provided, verify it
					if (isset($data['transaction_reference'])) {
						$paystack = new \Libs\PaystackClient();
						$transactionDetails = $paystack->verifyTransaction($data['transaction_reference']);

						if ($transactionDetails) {
							if ($transactionDetails['status'] == true) {
								if ($transactionDetails['data']['status'] == 'success') {
									// This transaction succeeded, save reference and mark the booking as paid.
									$entries['transaction_reference'] = $data['transaction_reference'];
									$entries['payment_status'] = 'PAID';
								} else {
									// No transaction failed
									// Save the reference only
									$entries['transaction_reference'] = $data['transaction_reference'];
								}
							}
						}
					}

					// Insert the data
					$insert_id = $this->model->createBooking($entries);

					if ($insert_id) {
						// Get the booking and return it
						$booking = $this->model->getBookingData($insert_id);

						return $this->respond($booking);
					}
					else {
						return $this->fail('Failed to add booking.');
					}
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
	
	public function getBookings() {
		try {
      // Set the headers
			$this->setDefaultHeaders();
			
			$user = $this->authenticateSession();

      if ($user) {
				$status = 'all';

				if ($this->request->getGet('status'))
					$status = \strtoupper($this->request->getGet('status'));

				if ($this->request->getGet('size') && is_numeric($this->request->getGet('size')))
					$size = $this->request->getGet('size');
				else 
					$size = null;

				if ($this->request->getGet('offset') && is_numeric($this->request->getGet('offset')))
					$offset = $this->request->getGet('offset');
				else
					$offset = 0;

				$bookings = $this->model->getBookings($user['id'], $size, $offset);
				if ($bookings) {
					$data = [];

					foreach($bookings as $booking) {
						$items = [];

						// Fetch all the items
						foreach (explode('|', $booking['items_ids']) as $item_id) {
							$items[] = $this->model->getBookedItem($item_id);
						}

						$booking['items'] = $items;
						$data[] = $booking;
					}

					return $this->respond($data);
				}
				else
					return $this->failNotFound('No booking matched your queries.');
      }
      else {
				return $this->failUnauthorized('Authentication failed!');
			}
    } catch(\Throwable $th) {
			$this->logException($th);
			return $this->failServerError();
    }
	}
	
	public function getBooking() {
		try {
      // Set the headers
			$this->setDefaultHeaders();
			
			$user = $this->authenticateSession();

      if ($user) {
				$data = $this->request->getJSON(true);
				if (!$data)
					return $this->failNotFound('No valid data was provided!');

				// Validation rules
				$validationRules = [
					'booking_id' => 'trim|required|is_not_unique[bookings.booking_id]',
				];

				// Validate the data
				$this->validation->setRules($validationRules);
				if ($this->validation->run($data)) {
					// Get the booking
					$booking = $this->model->getBookingDataById($data['booking_id']);

					// Make sure the user is authorized to view this booking
					if ($booking['user_id'] == $user['id']) {
						$items = [];

						// Fetch all the items
						foreach (explode('|', $booking['items_ids']) as $item_id) {
							$items[] = $this->model->getBookedItem($item_id);
						}

						$booking['items'] = $items;
						return $this->respond($booking);
					}
					else
						return $this->failResourceExists('This user is not authorized to access this booking.');
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