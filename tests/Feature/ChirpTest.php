<?php

namespace Tests\Feature;

use App\Models\Chirp;
use App\Models\User;
use Symfony\Component\CssSelector\Node\FunctionNode;
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

    public function test_un_chirp_ne_peut_pas_depasse_255_caracteres()
    {
        $utilisateur = User::factory()->create();
        $this->actingAs($utilisateur);
        $reponse = $this->post('/chirps', [
            'message' => str_repeat('a', 256)
        ]);
        $reponse->assertSessionHasErrors(['message']);
    }

    public function test_les_chirps_sont_affiches_sur_la_page_d_accueil()
    {
        $utilisateur = User::factory()->create();
        $this->actingAs($utilisateur);
        $chirps = Chirp::factory()->count(3)->create(['user_id' => $utilisateur->id]);
        $reponse = $this->get('/chirps');
        foreach ($chirps as $chirp) {
            $reponse->assertSee($chirp->message);
        }
    }

    public function test_un_utilisateur_peut_modifier_son_chirp()
    {
        $utilisateur = User::factory()->create();
        $chirp = Chirp::factory()->create(['user_id' => $utilisateur->id]);
        $this->actingAs($utilisateur);
        $reponse = $this->put("/chirps/{$chirp->id}", [
            'message' => 'Chirp modifié'
        ]);
        $reponse->assertStatus(302);
        // Vérifie si le chirp existe dans la base de donnée.
        $this->assertDatabaseHas('chirps', [
            'id' => $chirp->id,
            'message' => 'Chirp modifié',
        ]);
    }

    public function test_un_utilisateur_peut_supprimer_son_chirp() {
        $utilisateur = User::factory()->create();
        $this->actingAs($utilisateur);
        $chirp = Chirp::factory()->create(['user_id' => $utilisateur->id]);
        $reponse = $this->delete("/chirps/{$chirp->id}");
        $reponse->assertStatus(302);
        $this->assertDatabaseMissing('chirps', [
            'id' => $chirp->id,
        ]);
    }

    public function test_utilisateur_ne_peut_pas_modifier_ou_supprimer_chirp_d_un_autre_utilisateur() {
        $utilisateur1 = User::factory()->create();
        $utilisateur2 = User::factory()->create();
        $chirp = Chirp::factory()->create([
            'user_id' => $utilisateur1->id,
        ]);
        $this->actingAs($utilisateur2);
        $reponse = $this->put("/chirps/{$chirp->id}", [
            'message' => "Message modifié par l'utilisateur 2",
        ]);
        $reponse->assertStatus(403);

        $reponse = $this->delete("/chirps/{$chirp->id}");
        $reponse->assertStatus(403);
    }

    public function test_un_utilisateur_ne_peut_pas_modifier_un_chirp_en_donnant_une_valeur_nulle_ou_superieure_a_255_caracteres() {
        $utilisateur = User::factory()->create();
        $chirp = Chirp::factory()->create([
            'user_id' => $utilisateur->id,
        ]);
        $this->actingAs($utilisateur);
        $reponse = $this->put("/chirps/{$chirp->id}", [
            'message' => '',
        ]);
        $reponse->assertSessionHasErrors(['message']);

        $reponse = $this->put("/chirps/{$chirp->id}", [
            'message' => str_repeat('a',256),
        ]);
        $reponse->assertSessionHasErrors(['message']);
    }

    public function test_utilisateur_ne_peut_pas_creer_plus_de_10_chirps() {
        $utilisateur = User::factory()->create();
        Chirp::factory()->count(10)->create([
            'user_id' => $utilisateur->id,
        ]);
        $this->actingAs($utilisateur);
        $reponse = $this->post("/chirps",[
            'message' => 'Chirp 11',
        ]);
        $reponse->assertRedirect(route('chirps.index'));
        $reponse->assertSessionHasErrors(['message']);
    }

    public function test_seuls_les_chirps_recents_sont_affiches()
    {
        $utilisateur = User::factory()->create();
        $this->actingAs($utilisateur);

        Chirp::factory()->create([
            'message' => 'Chirp vieux de 8 jours',
            'created_at' => now()->subDays(8),
            'user_id' => $utilisateur->id,
        ]);

        Chirp::factory()->create([
            'message' => 'Chirp vieux de 6 jours',
            'created_at' => now()->subDays(6),
            'user_id' => $utilisateur->id,
        ]);

        Chirp::factory()->create([
            'message' => 'Chirp vieux de 3 jours',
            'created_at' => now()->subDays(3),
            'user_id' => $utilisateur->id,
        ]);

        Chirp::factory()->create([
            'message' => "Chirp créé aujourd'hui",
            'created_at' => now(),
            'user_id' => $utilisateur->id,
        ]);

        $reponse = $this->get('/chirps');

        $reponse->assertSee('Chirp vieux de 6 jours');
        $reponse->assertSee('Chirp vieux de 3 jours');
        $reponse->assertSee("Chirp créé aujourd\'hui");
        $reponse->assertDontSee('Chirp vieux de 8 jours');
    }

}