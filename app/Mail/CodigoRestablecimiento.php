<?php

namespace App\Mail;

use App\Models\Usuario;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class CodigoRestablecimiento extends Mailable
{
    public function __construct(public Usuario $usuario, public string $codigo) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'MeatSys - Código para restablecer su contraseña');
    }

    public function content(): Content
    {
        return new Content(view: 'emails.codigo');
    }
}
