<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CompanyResource\Pages;
use App\Models\Company;
use Filament\Forms;
use Filament\Forms\Components\Section;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class CompanyResource extends Resource
{
    protected static ?string $model = Company::class;
    protected static ?string $navigationIcon = 'heroicon-o-building-office-2';
    protected static ?string $navigationLabel = 'Clientes / Empresas';
    protected static ?string $navigationGroup = 'Clientes';
    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Section::make('Dados Cadastrais')->columns(2)->schema([
                Forms\Components\TextInput::make('nome')->label('Nome')->required()->maxLength(255),
                Forms\Components\TextInput::make('razao_social')->label('Razão Social')->maxLength(255),
                Forms\Components\TextInput::make('cnpj')->label('CNPJ')->maxLength(18),
                Forms\Components\TextInput::make('responsavel')->label('Responsável')->maxLength(255),
                Forms\Components\TextInput::make('telefone')->label('Telefone')->maxLength(20),
                Forms\Components\TextInput::make('email')->label('E-mail')->email()->maxLength(255),
                Forms\Components\TextInput::make('cidade')->label('Cidade')->maxLength(255),
                Forms\Components\TextInput::make('estado')->label('Estado')->maxLength(2),
                Forms\Components\TextInput::make('produto_principal')->label('Produto Principal')->maxLength(255),
                Forms\Components\Select::make('status')->label('Status')->options(['Implantação'=>'Implantação','Homologação'=>'Homologação','Ativo'=>'Ativo','Suspenso'=>'Suspenso'])->default('Implantação')->required(),
            ]),
            Section::make('Implantação e URLs')->description('Configurações adicionais de ambiente, domínios e status do projeto.')->columns(2)->schema([
                Forms\Components\TextInput::make('dominio_principal')->label('Domínio Principal')->placeholder('exemplo.com.br')->maxLength(255),
                Forms\Components\TextInput::make('dominio_landing')->label('Domínio da Landing')->placeholder('conhecasuacidade.com.br')->maxLength(255),
                Forms\Components\TextInput::make('dominio_demo')->label('Domínio de Demonstração')->placeholder('demo.vitrineiapro.com.br')->maxLength(255),
                Forms\Components\TextInput::make('url_provisoria')->label('URL Provisória')->placeholder('temp.vitrineiapro.com.br')->maxLength(255),
                Forms\Components\TextInput::make('url_admin')->label('URL Administrativa')->placeholder('admin.exemplo.com.br')->maxLength(255),
                Forms\Components\Select::make('ambiente')->label('Ambiente')->options(['Produção'=>'Produção','Homologação'=>'Homologação','Demo'=>'Demo','Desenvolvimento'=>'Desenvolvimento']),
                Forms\Components\Select::make('tipo_instancia')->label('Tipo de Instância')->options(['piloto'=>'Piloto','cliente'=>'Cliente','demo'=>'Demonstração','white_label'=>'White Label','interno'=>'Interno']),
                Forms\Components\Select::make('status_implantacao')->label('Status da Implantação')->options(['Não iniciado'=>'Não iniciado','Em implantação'=>'Em implantação','Homologação'=>'Homologação','Publicado'=>'Publicado','Suspenso'=>'Suspenso'])->default('Não iniciado')->required(),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            Tables\Columns\TextColumn::make('nome')->label('Cliente')->searchable()->sortable(),
            Tables\Columns\TextColumn::make('responsavel')->label('Responsável')->searchable()->toggleable(isToggledHiddenByDefault: true),
            Tables\Columns\TextColumn::make('dominio_principal')->label('Domínio Principal')->searchable(),
            Tables\Columns\TextColumn::make('dominio_landing')->label('Landing')->searchable()->toggleable(),
            Tables\Columns\TextColumn::make('tipo_instancia')->label('Instância')->badge()->toggleable(),
            Tables\Columns\TextColumn::make('ambiente')->label('Ambiente')->badge()->color(fn (?string $state): string => match ($state) { 'Produção'=>'success','Homologação'=>'warning','Demo'=>'info', default=>'gray' }),
            Tables\Columns\TextColumn::make('status_implantacao')->label('Implantação')->badge()->color(fn (string $state): string => match ($state) { 'Publicado'=>'success','Em implantação'=>'warning','Homologação'=>'info','Suspenso'=>'danger', default=>'gray' }),
            Tables\Columns\TextColumn::make('status')->badge()->color(fn (string $state): string => match ($state) { 'Ativo'=>'success','Homologação'=>'info','Implantação'=>'warning','Suspenso'=>'danger', default=>'gray' }),
        ])->filters([
            Tables\Filters\SelectFilter::make('status')->options(['Implantação'=>'Implantação','Homologação'=>'Homologação','Ativo'=>'Ativo','Suspenso'=>'Suspenso']),
            Tables\Filters\SelectFilter::make('ambiente')->options(['Produção'=>'Produção','Homologação'=>'Homologação','Demo'=>'Demo']),
        ])->actions([Tables\Actions\EditAction::make()])->bulkActions([Tables\Actions\BulkActionGroup::make([Tables\Actions\DeleteBulkAction::make()])]);
    }

    public static function getPages(): array
    {
        return ['index'=>Pages\ListCompanies::route('/'), 'create'=>Pages\CreateCompany::route('/create'), 'edit'=>Pages\EditCompany::route('/{record}/edit')];
    }
}
