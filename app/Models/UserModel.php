<?php namespace App\Models;

use CodeIgniter\Model;

class UserModel extends Model
{
  public function createUser(array $entries) {
    if ($this->db->table('users')->insert($entries)) {
      return $this->insertID();
    }

    return FALSE;
  }

  public function getUserData(string $username) {
    return $this->db->table('users')
      ->where('username', $username)
      ->get()->getRowArray();
  }

  public function updateUserData(int $id, array $entries) {
    return $this->db->table('users')
      ->where('id', $id)
      ->update($entries);
  }

  public function createBooking(array $entries) {
    if ($this->db->table('bookings')->insert($entries)) {
      return $this->insertID();
    }

    return FALSE;
  }

  public function updateBooking(int $id, array $entries) {
    return $this->db->table('bookings')
      ->where('id', $id)
      ->update($entries);
  }

  public function getBookingData(int $id) {
    return $this->db->table('bookings')
      ->where('id', $id)
      ->get()->getRowArray();
  }

  public function getBookingDataById($booking_id) {
    return $this->db->table('bookings')
      ->where('booking_id', $booking_id)
      ->get()->getRowArray();
  }

  public function getBookingDataByTransactionReference($transaction_reference) {
    return $this->db->table('bookings')
      ->where('transaction_reference', $transaction_reference)
      ->get()->getRowArray();
  }

  public function getBookedItem($id) {
    return $this->db->table('booked_items')
      ->where('id', $id)
      ->get()->getRowArray();
  }

  public function getBookings(int $user_id, $size, int $offset, string $status = 'all') {
    if ($status == 'all') {
      return $this->db->table('bookings')
        ->where('user_id', $user_id)
        ->orderBy('date_added', 'DESC')
        ->get($size, $offset)->getResultArray();
    }
    else {
      return $this->db->table('bookings')
      ->where([
        'user_id' => $user_id,
        'status'  => $status,
      ])
      ->orderBy('date_added', 'DESC')
      ->get($size, $offset)->getResultArray();
    }
  }
}