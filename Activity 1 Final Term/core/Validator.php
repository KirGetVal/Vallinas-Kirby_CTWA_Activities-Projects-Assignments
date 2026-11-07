<?php
declare(strict_types=1);

final class Validator
{
    private array $errors = [];

    public function __construct(private array $data) {}

    public static function clean(mixed $v): string
    {
        return trim(preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F]/', '', (string)$v));
    }

    public function get(string $k): string
    {
        return self::clean($this->data[$k] ?? '');
    }

    public function required(string $k, string $label): self
    {
        if ($this->get($k) === '') {
            $this->errors[$k] = "$label is required.";
        }
        return $this;
    }

    public function length(string $k, string $label, int $min, int $max): self
    {
        $len = str_len($this->get($k));
        if ($this->get($k) !== '' && ($len < $min || $len > $max)) {
            $this->errors[$k] = "$label must be between $min and $max characters.";
        }
        return $this;
    }

    public function email(string $k): self
    {
        if ($this->get($k) !== '' && !filter_var($this->get($k), FILTER_VALIDATE_EMAIL)) {
            $this->errors[$k] = 'Enter a valid email address.';
        }
        return $this;
    }

    public function password(string $k): self
    {
        $p = (string)($this->data[$k] ?? '');
        if (strlen($p) < 8 || !preg_match('/[A-Za-z]/', $p) || !preg_match('/\d/', $p)) {
            $this->errors[$k] = 'Password must be at least 8 characters and include a letter and a number.';
        }
        return $this;
    }

    public function date(string $k, string $label, bool $notFuture = true): self
    {
        $v = $this->get($k);
        $d = DateTime::createFromFormat('Y-m-d', $v);
        if ($v !== '' && (!$d || $d->format('Y-m-d') !== $v)) {
            $this->errors[$k] = "$label is not a valid date.";
        } elseif ($v !== '' && $notFuture && $d > new DateTime('today')) {
            $this->errors[$k] = "$label cannot be in the future.";
        }
        return $this;
    }

    public function in(string $k, string $label, array $allowed): self
    {
        if (!in_array($this->get($k), $allowed, true)) {
            $this->errors[$k] = "Select a valid $label.";
        }
        return $this;
    }

    public function fail(string $k, string $msg): self
    {
        $this->errors[$k] = $msg;
        return $this;
    }

    public function passes(): bool { return $this->errors === []; }
    public function errors(): array { return $this->errors; }
}
