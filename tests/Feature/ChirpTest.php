<?php

namespace Tests\Feature;

use App\Models\User;
use Tests\TestCase;

class ChirpTest extends TestCase
{
    /**
     * A basic feature test example.
     */
    public function test_example(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200);
    }

    public function test_un_utilisateur_peut_creer_un_chirp()
    {
        // Simuler un utilisateur connecté
        $utilisateur = User::factory()->create();
        $this->actingAs($utilisateur);
        // Envoyer une requête POST pour créer un chirp
        $reponse = $this->post('/chirps', [
            'message' => 'Wesh'
        ]);
        // Vérifier que le chirp a été ajouté à la base de données
        $reponse->assertStatus(302);
        $this->assertDatabaseHas('chirps', [
            'message' => 'Wesh',
            'user_id' => $utilisateur->id,
        ]);
    }

    public function test_un_chirp_ne_peut_pas_avoir_un_contenu_vide()
    {
        $utilisateur = User::factory()->create();
        $this->actingAs($utilisateur);
        $reponse = $this->post('/chirps', [
            'message' => ''
        ]);
        $reponse->assertSessionHasErrors(['message']);
    }

    public function test_un_chirp_ne_peut_pas_depasse_255_caracteres() {
        $utilisateur = User::factory()->create();
        $this->actingAs($utilisateur);
        $reponse = $this->post('/chirps', [
            'message' => str_repeat('a', 256)
        ]);
        $reponse->assertSessionHasErrors(['message']);
    }
}
