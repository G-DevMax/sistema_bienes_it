<?php

namespace Tests\Feature;

use Tests\TestCase;

class ErrorPagesTest extends TestCase
{
    public function test_pagina_404_personalizada_se_renderiza(): void
    {
        $response = $this->get('/ruta-que-definitivamente-no-existe-xyz');

        $response->assertStatus(404);
        $response->assertSee('404');
        $response->assertSee('Página no encontrada');
    }

    public function test_vista_500_se_renderiza_correctamente(): void
    {
        $view = $this->view('errors.500');

        $view->assertSee('500');
        $view->assertSee('Error interno del servidor');
    }

    public function test_vista_503_se_renderiza_correctamente(): void
    {
        $view = $this->view('errors.503');

        $view->assertSee('503');
        $view->assertSee('Sistema en Mantenimiento');
    }
}
