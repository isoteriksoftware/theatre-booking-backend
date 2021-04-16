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

  public function addBooking(array $entries) {
    if ($this->db->table('bookings')->insert($entries)) {
      return $this->insertID();
    }

    return FALSE;
  }

  public function addTicket(array $entries) {
    if ($this->db->table('tickets')->insert($entries)) {
      return $this->insertID();
    }

    return FALSE;
  }

  public function getTotalTickets($show_id) {
    return $this->db->table('tickets')
      ->select('COUNT(id) AS total')
      ->where('show_id', $show_id)
      ->get()->getRowArray()['total'];
  }
}