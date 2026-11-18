<?php

namespace App\Mail;

use App\Models\Factura;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class FacturaVenta extends Mailable
{
    public function __construct(public Factura $factura, public string $rutaPdf) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: config('meatsys.negocio.nombre').' - Factura '.$this->factura->numeroFactura);
    }

    public function content(): Content
    {
        return new Content(view: 'emails.factura');
    }

    public function attachments(): array
    {
        return [Attachment::fromPath($this->rutaPdf)->as($this->factura->numeroFactura.'.pdf')->withMime('application/pdf')];
    }
}
