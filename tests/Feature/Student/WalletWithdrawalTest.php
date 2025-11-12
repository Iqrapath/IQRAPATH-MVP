<?php

use App\Models\User;
use App\Models\StudentWallet;
use App\Models\PaymentMethod;
use App\Models\PayoutRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->student = User::factory()->create([
        'role' => 'student',
        'email_verified_at' => now(),
    ]);
    
    $this->wallet = StudentWallet::factory()->create([
        'user_id' => $this->student->id,
        'balance' => 10000.00,
    ]);
});

it('allows student to request withdrawal with valid data', function () {
    $paymentMethod = PaymentMethod::factory()->create([
        'user_id' => $this->student->id,
        'type' => 'bank_transfer',
        'is_verified' => true,
        'is_active' => true,
        'bank_name' => 'Test Bank',
        'bank_code' => '058',
        'account_name' => 'Test Student',
        'account_number' => '1234567890',
        'last_four' => '7890',
    ]);
    
    $response = $this->actingAs($this->student)
        ->postJson(route('student.wallet.withdraw'), [
            'amount' => 5000,
            'payment_method_id' => $paymentMethod->id,
            'notes' => 'Test withdrawal',
        ]);
    
    $response->assertSuccessful()
        ->assertJson([
            'success' => true,
            'message' => 'Withdrawal request submitted successfully',
        ]);
    
    // Verify payout request was created
    $payoutRequest = PayoutRequest::where('user_id', $this->student->id)->first();
    expect($payoutRequest)->not->toBeNull();
    expect((float) $payoutRequest->amount)->toBe(5000.0);
    expect($payoutRequest->status)->toBe('pending');
    expect($payoutRequest->payment_method)->toBe('bank_transfer');
    
    // Verify wallet balance was deducted
    expect((float) $this->wallet->fresh()->balance)->toBe(5000.0);
});

it('prevents withdrawal below minimum amount', function () {
    $paymentMethod = PaymentMethod::factory()->create([
        'user_id' => $this->student->id,
        'type' => 'bank_transfer',
        'is_verified' => true,
        'is_active' => true,
    ]);
    
    $response = $this->actingAs($this->student)
        ->postJson(route('student.wallet.withdraw'), [
            'amount' => 400, // Below ₦500 minimum
            'payment_method_id' => $paymentMethod->id,
        ]);
    
    $response->assertStatus(422);
});

it('prevents withdrawal exceeding wallet balance', function () {
    $paymentMethod = PaymentMethod::factory()->create([
        'user_id' => $this->student->id,
        'type' => 'bank_transfer',
        'is_verified' => true,
        'is_active' => true,
    ]);
    
    $response = $this->actingAs($this->student)
        ->postJson(route('student.wallet.withdraw'), [
            'amount' => 15000, // More than wallet balance
            'payment_method_id' => $paymentMethod->id,
        ]);
    
    $response->assertStatus(400)
        ->assertJson([
            'success' => false,
        ]);
});

it('prevents withdrawal with unverified bank account', function () {
    $paymentMethod = PaymentMethod::factory()->create([
        'user_id' => $this->student->id,
        'type' => 'bank_transfer',
        'is_verified' => false, // Not verified
        'is_active' => true,
    ]);
    
    $response = $this->actingAs($this->student)
        ->postJson(route('student.wallet.withdraw'), [
            'amount' => 5000,
            'payment_method_id' => $paymentMethod->id,
        ]);
    
    $response->assertStatus(400)
        ->assertJson([
            'success' => false,
            'message' => 'Invalid payment method. Please select a verified bank account.',
        ]);
});

it('prevents withdrawal with non-bank-transfer payment method', function () {
    $paymentMethod = PaymentMethod::factory()->create([
        'user_id' => $this->student->id,
        'type' => 'card', // Not bank transfer
        'is_verified' => true,
        'is_active' => true,
    ]);
    
    $response = $this->actingAs($this->student)
        ->postJson(route('student.wallet.withdraw'), [
            'amount' => 5000,
            'payment_method_id' => $paymentMethod->id,
        ]);
    
    $response->assertStatus(400)
        ->assertJson([
            'success' => false,
            'message' => 'Invalid payment method. Please select a verified bank account.',
        ]);
});

it('prevents duplicate pending withdrawal requests', function () {
    $paymentMethod = PaymentMethod::factory()->create([
        'user_id' => $this->student->id,
        'type' => 'bank_transfer',
        'is_verified' => true,
        'is_active' => true,
    ]);
    
    // Create existing pending request
    PayoutRequest::factory()->create([
        'user_id' => $this->student->id,
        'status' => 'pending',
        'amount' => 2000,
    ]);
    
    $response = $this->actingAs($this->student)
        ->postJson(route('student.wallet.withdraw'), [
            'amount' => 5000,
            'payment_method_id' => $paymentMethod->id,
        ]);
    
    $response->assertStatus(400)
        ->assertJson([
            'success' => false,
            'message' => 'You already have a pending withdrawal request. Please wait for it to be processed.',
        ]);
});

it('prevents non-students from requesting withdrawals', function () {
    $teacher = User::factory()->create([
        'role' => 'teacher',
        'email_verified_at' => now(),
    ]);
    
    $paymentMethod = PaymentMethod::factory()->create([
        'user_id' => $teacher->id,
        'type' => 'bank_transfer',
        'is_verified' => true,
        'is_active' => true,
    ]);
    
    // The route has role:student middleware, so it will redirect (302) instead of returning 403
    $response = $this->actingAs($teacher)
        ->postJson(route('student.wallet.withdraw'), [
            'amount' => 5000,
            'payment_method_id' => $paymentMethod->id,
        ]);
    
    // Expect redirect due to middleware
    $response->assertStatus(302);
});

it('creates wallet transaction record on withdrawal', function () {
    $paymentMethod = PaymentMethod::factory()->create([
        'user_id' => $this->student->id,
        'type' => 'bank_transfer',
        'is_verified' => true,
        'is_active' => true,
    ]);
    
    $this->actingAs($this->student)
        ->postJson(route('student.wallet.withdraw'), [
            'amount' => 5000,
            'payment_method_id' => $paymentMethod->id,
        ]);
    
    // Verify wallet transaction was created
    $transaction = $this->wallet->transactions()
        ->where('transaction_type', 'debit')
        ->where('amount', 5000)
        ->first();
    
    expect($transaction)->not->toBeNull();
    expect($transaction->status)->toBe('pending');
});
