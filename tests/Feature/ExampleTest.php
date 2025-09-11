<?php

test('basic test works', function () {
    expect(true)->toBeTrue();
});

test('homepage loads', function () {
    $response = $this->get('/');

    $response->assertStatus(200);
});
