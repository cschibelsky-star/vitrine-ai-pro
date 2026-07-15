<?php

namespace App\Commercial\Models;

use Illuminate\Database\Eloquent\Model;

class Lead extends Model
{
    protected $table = 'leads';

    protected $guarded = [];

    protected $casts = [
        'data_proxima_acao' => 'date',
        'valor_estimado' => 'decimal:2',
        'consentimento_lgpd' => 'boolean',
        'capturado_em' => 'datetime',
        'metadata' => 'array',
    ];

    public static function origemOptions(): array
    {
        return [
            'WhatsApp' => 'WhatsApp',
            'Site' => 'Site',
            'Conheça Sumaré' => 'Conheça Sumaré',
            'Conheça Sua Cidade' => 'Conheça Sua Cidade',
            'Vitrine AI Pro' => 'Vitrine AI Pro',
            'TV Digital Enterprise' => 'TV Digital Enterprise',
            'Vitrine AI Pro News' => 'Vitrine AI Pro News',
            'Cristian Autismo' => 'Cristian Autismo',
            'RT Bem Viver' => 'RT Bem Viver',
            'Indicação' => 'Indicação',
            'Instagram' => 'Instagram',
            'Facebook' => 'Facebook',
            'Google' => 'Google',
            'Prospecção ativa' => 'Prospecção ativa',
            'Evento' => 'Evento',
            'Cliente atual' => 'Cliente atual',
            'Outro' => 'Outro',
        ];
    }

    public static function planoOptions(): array
    {
        return [
            'Start' => 'Start',
            'Pro' => 'Pro',
            'Enterprise' => 'Enterprise',
            'Governo' => 'Governo',
            'White Label' => 'White Label',
            'Sob proposta' => 'Sob proposta',
        ];
    }

    public static function statusNegociacaoOptions(): array
    {
        return [
            'Novo' => 'Novo',
            'Contato' => 'Contato',
            'Diagnóstico' => 'Diagnóstico',
            'Demonstração' => 'Demonstração',
            'Proposta' => 'Proposta',
            'Negociação' => 'Negociação',
            'Fechado' => 'Fechado',
            'Perdido' => 'Perdido',
        ];
    }

    public static function proximaAcaoOptions(): array
    {
        return [
            'Enviar apresentação' => 'Enviar apresentação',
            'Agendar demonstração' => 'Agendar demonstração',
            'Enviar proposta' => 'Enviar proposta',
            'Fazer follow-up' => 'Fazer follow-up',
            'Aguardar retorno' => 'Aguardar retorno',
            'Reunião técnica' => 'Reunião técnica',
            'Reunião comercial' => 'Reunião comercial',
            'Converter em cliente' => 'Converter em cliente',
            'Encerrar oportunidade' => 'Encerrar oportunidade',
        ];
    }
}
