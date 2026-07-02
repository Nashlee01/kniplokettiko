<?php

// Unit test: kleine losse logica zonder routes/controllers of database.
it('zet een postcode om naar hoofdletters', function (): void {
    $postcode = strtoupper('1234ab');

    expect($postcode)->toBe('1234AB');
});

it('controleert dat een huisnummer groter dan 0 is', function (): void {
    $huisnummer = 5;

    expect($huisnummer)->toBeGreaterThan(0);
});
