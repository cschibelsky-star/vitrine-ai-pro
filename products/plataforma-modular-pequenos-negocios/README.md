# Plataforma Modular para Pequenos Negócios

Projeto independente da Gestão de Cadastro.

## MVP
- Base multiempresa
- Clientes
- Profissionais
- Serviços
- Agenda
- Licenciamento por empresa e módulo

## Roadmap
Financeiro, Caixa, Estoque, Comissões, PDV e Marketing.

## Regras arquiteturais
- isolamento por tenant_id;
- módulos habilitados por empresa;
- nenhuma dependência de dados ou código da Gestão de Cadastro;
- HML próprio;
- API de health;
- persistência SQLite no MVP, com caminho de evolução para banco relacional dedicado.

## HML
Serviço: plataforma_modular_hml
Porta local: 8897
