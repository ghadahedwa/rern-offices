<?php

test('the root url redirects to the citizen feedback portal', function () {
    $this->get(route('home'))->assertRedirect(route('feedback'));
});

test('the citizen feedback portal is reachable without logging in', function () {
    $this->get(route('feedback'))->assertOk();
});
