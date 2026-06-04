<?php

namespace App\DTOs\Auth;

class RegisterDTO
{
    public function __construct(
        public string $company_name,
        public string $company_website,
        public ?int $company_size,
        public string $phone_number,
        public string $email,
        public string $evaluating_website,
        public int $role,
        public string $password
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            company_name: $data['company_name'],
            company_website: $data['company_website'],
            company_size: $data['company_size'] ?? null,
            phone_number: $data['phone_number'],
            email: $data['email'],
            evaluating_website: $data['evaluating_website'],
            role: $data['role'],
            password: $data['password']
        );
    }

    public function toArray(): array
    {
        return [
            'company_name' => $this->company_name,
            'company_website' => $this->company_website,
            'company_size' => $this->company_size,
            'phone_number' => $this->phone_number,
            'email' => $this->email,
            'evaluating_website' => $this->evaluating_website,
            'role' => $this->role,
            'password' => $this->password,
        ];
    }
}