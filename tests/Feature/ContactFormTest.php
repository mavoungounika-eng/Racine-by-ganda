<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Support\Facades\Mail;
use App\Mail\ContactFormMail;

class ContactFormTest extends TestCase
{
    /**
     * Test that contact form page loads correctly
     */
    public function test_contact_page_loads()
    {
        $response = $this->get(route('frontend.contact'));

        $response->assertStatus(200);
        $response->assertViewIs('frontend.contact');
    }

    /**
     * Test contact form submission with valid data
     */
    public function test_contact_form_submission_valid()
    {
        Mail::fake();

        $response = $this->post(route('frontend.contact.submit'), [
            'first_name' => 'Jean',
            'last_name' => 'Dupont',
            'email' => 'jean@example.com',
            'phone' => '+242 06 123 456',
            'subject' => 'product',
            'message' => 'Je souhaite connaître plus d\'informations sur ce produit.',
        ]);

        $response->assertRedirect(route('frontend.contact'));
        $response->assertSessionHas('success');

        Mail::assertSent(ContactFormMail::class);
    }

    /**
     * Test contact form submission validation errors
     */
    public function test_contact_form_submission_invalid()
    {
        $response = $this->post(route('frontend.contact.submit'), [
            'first_name' => '',
            'last_name' => '',
            'email' => 'invalid-email',
            'message' => 'too short',
        ]);

        $response->assertSessionHasErrors(['first_name', 'last_name', 'email', 'subject', 'message']);
    }

    /**
     * Test mail content is correct
     */
    public function test_contact_mail_format()
    {
        Mail::fake();

        $data = [
            'first_name' => 'Alice',
            'last_name' => 'Martin',
            'email' => 'alice@example.com',
            'phone' => '+242 06 987 654',
            'subject' => 'partnership',
            'message' => 'Nous aimerions collaborer avec votre marque.',
        ];

        Mail::to('test@example.com')->send(new ContactFormMail($data));

        Mail::assertSent(ContactFormMail::class, function ($mail) use ($data) {
            return $mail->subject === "[Contact] {$data['subject']} - {$data['first_name']} {$data['last_name']}";
        });
    }
}
