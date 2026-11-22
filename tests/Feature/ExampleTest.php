<?php

test('the application redirects unauthenticated users to login', function () {
    $response = $this->get('/');

    $response->assertStatus(302); // Atau gunakan ->assertStatus(302);
});