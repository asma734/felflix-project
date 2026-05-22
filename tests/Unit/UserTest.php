<?php
namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
require_once __DIR__ . '/../../model/User.php';

class UserTest extends TestCase {
    public function testUserCreation() {
        $user = new \User('John Doe', 'john@example.com', 'password123');
        $this->assertEquals('John Doe', $user->nom);
        $this->assertEquals('john@example.com', $user->email);
        $this->assertEquals('password123', $user->password);
        $this->assertEquals('user', $user->role);
        $this->assertEquals('🌶', $user->avatar);
        $this->assertEquals('', $user->bio);
    }
}