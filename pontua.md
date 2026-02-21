# SIGPONTO — Sistema de Controle de Frequência

> Especificação funcional e técnica para desenvolvimento do sistema de ponto digital da AGED-MA.  
> Baseado na Folha Individual de Frequência utilizada atualmente em papel.

---

## 1. Visão Geral

O SIGPONTO substitui a folha manual de frequência por um sistema digital onde cada servidor registra sua entrada e saída por turno. O responsável pela frequência pode, ao final do período, exportar o relatório em PDF ou Excel, fiel ao modelo da folha física atual.

---

## 2. Entidades e Cadastros

### 2.1 Servidor (Funcionário)

Cada servidor possui um cadastro fixo com os seguintes campos:

| Campo | Tipo | Descrição |
|---|---|---|
| `inscricao` | string | Número de inscrição funcional (ex: `16203/25`) |
| `nome` | string | Nome completo (ex: `João Pedro Santiago Lima`) |
| `lotacao` | string | Setor/diretoria (ex: `Diretoria de Tecnologia e Informação`) |
| `cargo_funcao` | string | Cargo ou função (ex: `FAPEMA`) |
| `turno` | enum | `MANHA` ou `TARDE` |
| `rubrica` | string | Identificador visual/assinatura do servidor para o ponto |

> **Nota:** O campo `rubrica` pode ser, num primeiro momento, apenas o nome abreviado ou iniciais do servidor, que serão exibidos na célula do ponto no relatório impresso.

---

### 2.2 Turno e Horários Fixos

Os horários são fixos e não editáveis pelo servidor, apenas pelo administrador:

| Turno | Entrada | Saída |
|---|---|---|
| Manhã | 08:00 | 14:00 |
| Tarde | 13:00 | 19:00 |

> O sistema armazena o turno do servidor e usa os horários fixos correspondentes ao gerar o relatório.

---

### 2.3 Período de Competência

Cada folha de ponto pertence a um **período**, definido por:

| Campo | Tipo | Descrição |
|---|---|---|
| `mes` | integer | Mês de referência (1–12) |
| `ano` | integer | Ano de referência |
| `data_inicio` | date | Ex: `01/01/2026` |
| `data_fim` | date | Ex: `31/01/2026` |

---

## 3. Cadastro de Dias Especiais

### 3.1 Finais de Semana

O sistema deve identificar automaticamente os dias da semana.  
Dias que caem em **sábado (6)** ou **domingo (0/7)** são automaticamente marcados como não úteis e não exibem botões de ponto.

### 3.2 Feriados

O administrador pode cadastrar feriados para um determinado ano. Campos:

| Campo | Tipo | Descrição |
|---|---|---|
| `data` | date | Data do feriado |
| `descricao` | string | Nome do feriado (ex: `Carnaval`, `Tiradentes`) |
| `tipo` | enum | `NACIONAL`, `ESTADUAL`, `MUNICIPAL` |

Feriados são exibidos na coluna de observação do dia correspondente no relatório.

---

## 4. Registro de Ponto

### 4.1 Estrutura do Registro

Cada batida de ponto é um registro com:

| Campo | Tipo | Descrição |
|---|---|---|
| `id` | uuid | Identificador único |
| `servidor_id` | fk | Referência ao servidor |
| `periodo_id` | fk | Referência ao período (mês/ano) |
| `dia` | integer | Dia do mês (1–31) |
| `tipo_batida` | enum | `ENTRADA_MANHA`, `SAIDA_MANHA`, `ENTRADA_TARDE`, `SAIDA_TARDE` |
| `hora_registrada` | time | Hora no momento em que o botão foi clicado |
| `status` | enum | `PRESENTE`, `FALTA`, `LIBERADO`, `FERIAS`, `FERIADO`, `FIM_DE_SEMANA` |

> **Importante:** A hora é registrada automaticamente no momento do clique, usando `now()` do servidor. Não há input manual de hora pelo servidor.

---

### 4.2 Botões de Batida de Ponto

A tela do servidor exibe os botões de acordo com o seu turno:

#### Turno Manhã:
```
[ Bater Entrada Manhã ]   [ Bater Saída Manhã ]
```

#### Turno Tarde:
```
[ Bater Entrada Tarde ]   [ Bater Saída Tarde ]
```

**Regras de exibição dos botões:**
- O botão de **Saída** só é habilitado após a **Entrada** ser registrada no mesmo dia.
- Uma vez batido o ponto (entrada ou saída), o botão é desabilitado e exibe a hora registrada no lugar.
- Em dias de **final de semana** ou **feriado**, os botões não são exibidos — apenas uma tag informativa.
- Em dias com **observação** (falta, férias, etc.), os botões de ponto são ocultados.

---

## 5. Observações por Dia

O responsável pela frequência (ou o servidor com permissão) pode adicionar uma observação em qualquer dia do mês para um servidor. As observações possíveis são:

| Código | Descrição exibida no relatório |
|---|---|
| `FALTA` | Falta injustificada |
| `FALTA_JUSTIFICADA` | Falta justificada |
| `LIBERADO` | Dispensado / Liberado |
| `FERIAS` | Férias |
| `AFASTAMENTO_MEDICO` | Afastamento médico |
| `SERVICO_EXTERNO` | Serviço externo |
| `LIVRE` | Campo de texto livre (até 100 caracteres) |

> A observação substitui o registro de ponto do dia. Dias com observação não devem exibir os botões de batida.

---

## 6. Perfis de Acesso

| Perfil | Permissões |
|---|---|
| **Servidor** | Ver e bater o próprio ponto; ver o próprio histórico |
| **Responsável** | Tudo do Servidor + adicionar observações a qualquer dia de qualquer servidor + exportar relatórios |
| **Administrador** | Tudo do Responsável + cadastrar servidores, períodos e feriados |

---

## 7. Telas Principais

### 7.1 Tela do Servidor — Bater Ponto

- Exibe as informações do servidor logado: nome, inscrição, lotação, cargo, turno.
- Exibe o mês/ano atual e os dias do mês em formato de lista ou calendário.
- Para cada dia útil (não feriado, não FDS), exibe o status atual e os botões disponíveis.
- Dias passados sem ponto registrado devem ser destacados visualmente (ex: fundo vermelho claro).

### 7.2 Tela do Responsável — Gestão de Frequência

- Lista todos os servidores com filtros por lotação e período.
- Ao clicar em um servidor, abre a visualização da folha de ponto dele para o período selecionado.
- Permite adicionar/editar observações em qualquer dia.
- Botão de **Exportar PDF** e **Exportar Excel** com o layout da folha de frequência oficial.

### 7.3 Tela de Administração

- CRUD de Servidores.
- CRUD de Feriados por ano.
- Configuração de turnos e horários.
- Abertura e fechamento de períodos (mês/ano).

---

## 8. Exportação de Relatórios

### 8.1 Exportar para PDF

O PDF deve replicar fielmente o layout da **Folha Individual de Frequência** da AGED-MA:

- Cabeçalho com brasão do Estado do Maranhão.
- Título: `FOLHA INDIVIDUAL DE FREQUÊNCIA`.
- Campos de cabeçalho: Inscrição, Nome, Mês/Ano, Cargo/Função, Lotação.
- Tabela com colunas: DIA | MANHÃ (Entrada Hora / Rubrica / Saída Hora / Rubrica) | TARDE (Entrada Hora / Rubrica / Saída Hora / Rubrica).
- A coluna "Rubrica" deve exibir o valor do campo `rubrica` do servidor quando há ponto registrado.
- Dias de final de semana: linha com fundo cinza claro ou indicativo visual.
- Dias com feriado: exibir o nome do feriado na coluna.
- Dias com observação: exibir o texto da observação no campo correspondente.
- Rodapé com campos: Observação, Visto, Responsável pela frequência, Assinatura do Chefe Imediato.

### 8.2 Exportar para Excel

Planilha com as mesmas colunas da tabela do PDF:

```
DIA | ENTRADA_HORA | RUBRICA | SAIDA_HORA | RUBRICA | ENTRADA_TARDE | RUBRICA | SAIDA_TARDE | RUBRICA | OBSERVACAO
```

- Linha de cabeçalho com os dados do servidor (Nome, Inscrição, Lotação, Cargo, Mês/Ano).
- Formatação de células para final de semana e feriado.
- Linha de rodapé com totalizadores (dias trabalhados, faltas, férias, etc.).

---

## 9. Modelo de Dados (Resumo)

```
servidores
  - id
  - inscricao
  - nome
  - lotacao
  - cargo_funcao
  - turno (MANHA | TARDE)
  - rubrica
  - ativo

periodos
  - id
  - mes
  - ano
  - data_inicio
  - data_fim
  - status (ABERTO | FECHADO)

feriados
  - id
  - data
  - descricao
  - tipo (NACIONAL | ESTADUAL | MUNICIPAL)

registros_ponto
  - id
  - servidor_id (fk)
  - periodo_id (fk)
  - dia
  - tipo_batida (ENTRADA_MANHA | SAIDA_MANHA | ENTRADA_TARDE | SAIDA_TARDE)
  - hora_registrada
  - created_at

observacoes_dia
  - id
  - servidor_id (fk)
  - periodo_id (fk)
  - dia
  - tipo (FALTA | FALTA_JUSTIFICADA | LIBERADO | FERIAS | AFASTAMENTO_MEDICO | SERVICO_EXTERNO | LIVRE)
  - descricao_livre (nullable)
  - criado_por (fk -> usuario)

usuarios
  - id
  - servidor_id (fk, nullable — admins podem não ser servidores)
  - email
  - senha_hash
  - perfil (SERVIDOR | RESPONSAVEL | ADMINISTRADOR)
```

---

## 10. Regras de Negócio

1. **Um servidor só pode bater o ponto do dia atual.** Não é permitido registrar ponto em dias passados ou futuros (exceto pelo Administrador para correções).
2. **Não é possível bater ponto em dia de final de semana ou feriado cadastrado.**
3. **Observações têm prioridade sobre o ponto.** Se houver uma observação (ex: Férias) no dia, o ponto não pode ser registrado.
4. **O período deve estar aberto** para permitir registros. Períodos fechados são somente leitura.
5. **Cada tipo de batida só pode ocorrer uma vez por dia por servidor.** Duplicatas devem ser rejeitadas.
6. **A hora registrada é sempre a hora do servidor** (backend), nunca do cliente, para evitar fraudes.
7. **Finais de semana são determinados automaticamente** pelo dia da semana calculado a partir da data — não precisam ser cadastrados.
8. **O relatório PDF/Excel deve refletir exatamente o estado no momento da exportação**, incluindo registros, observações e feriados.

---

## 11. Stack Sugerida

| Camada | Tecnologia |
|---|---|
| Backend | Laravel 11 |
| Frontend/Admin | Filament 3 |
| Banco de Dados | PostgreSQL |
| Exportação PDF | `barryvdh/laravel-dompdf` ou `spatie/laravel-pdf` |
| Exportação Excel | `maatwebsite/excel` (Laravel Excel) |
| Autenticação | Laravel Sanctum ou Filament Auth |

---

## 12. Fases de Desenvolvimento Sugeridas

### Fase 1 — Base
- [ ] Migrations e models (servidores, períodos, feriados, registros_ponto, observacoes_dia, usuarios)
- [ ] Seeders com dados de teste
- [ ] Autenticação com perfis

### Fase 2 — Funcionalidade Core
- [ ] Tela de bater ponto (servidor)
- [ ] Lógica de validação de batidas (dia útil, turno, duplicatas, período aberto)
- [ ] Cadastro de observações pelo responsável

### Fase 3 — Gestão
- [ ] Painel do responsável (visão geral dos servidores)
- [ ] CRUD de feriados
- [ ] Abertura/fechamento de períodos

### Fase 4 — Exportação
- [ ] Exportação PDF (layout fiel à folha física)
- [ ] Exportação Excel

### Fase 5 — Polimento
- [ ] Alertas visuais para dias sem ponto
- [ ] Dashboard com resumo do mês (dias trabalhados, faltas, etc.)
- [ ] Log de auditoria (quem registrou ou alterou cada ponto)
