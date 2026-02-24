# PRD: Sistema de Registro de Ponto (Pontua)

## Introdução

Sistema de controle de frequência para colaboradores utilizando Filament v5. O sistema permite que colaboradores registrem seus pontos de entrada e saída nos turnos manhã (08:00–14:00) e tarde (13:00–19:00), com visualização de frequência e alerta de dias sem marcação. Um gestor cadastra colaboradores, define feriados/pontos facultativos e exporta a folha de frequência individual no formato idêntico ao template Excel existente (`assets/FREQUÊNCIA - MODELO.xlsx`).

## Objetivos

- Permitir marcação de ponto simples e rápida (um clique) pelo colaborador
- Registrar automaticamente horário e rúbrica (nome do colaborador) ao marcar ponto
- Exibir para o colaborador seus pontos marcados e destacar dias úteis sem marcação
- Permitir ao gestor cadastrar colaboradores, definir turnos e gerenciar feriados
- Exportar folha individual de frequência em Excel/PDF no formato exato do template
- Três níveis de acesso: Admin, Gestor e Colaborador

## Histórias de Usuário

### US-001: Modelagem do banco de dados
**Descrição:** Como desenvolvedor, preciso criar as tabelas necessárias para armazenar colaboradores, turnos, pontos, feriados e configurações.

**Critérios de Aceite:**
- [x] Migration para tabela `employees` com campos: name, inscription (matrícula), department (lotação), position (cargo/função), organization, default_shift (enum: morning/afternoon), user_id (FK)
- [x] Migration para tabela `time_entries` com campos: employee_id (FK), date, morning_entry (time nullable), morning_exit (time nullable), afternoon_entry (time nullable), afternoon_exit (time nullable), shift_override (enum nullable: morning/afternoon — quando diferente do turno padrão)
- [x] Migration para tabela `holidays` com campos: date, name, type (enum: holiday/optional/partial — feriado, ponto facultativo ou dispensa parcial), recurrent (boolean), scope (enum: all/partial — se libera todos ou apenas alguns colaboradores)
- [x] Migration para tabela `holiday_employee` (pivot) com campos: holiday_id (FK), employee_id (FK), reason (string — motivo da dispensa). Usada quando o feriado/dispensa é parcial (apenas alguns colaboradores liberados)
- [x] Models Eloquent com relacionamentos, casts e factories
- [x] Seeders úteis para desenvolvimento
- [x] Testes passando

### US-002: Painel Admin — Gerenciamento de Usuários
**Descrição:** Como admin, quero gerenciar todos os usuários do sistema (admins, gestores e colaboradores) para controlar o acesso.

**Critérios de Aceite:**
- [x] Resource Filament para User com CRUD completo
- [x] Campo de role (admin, manager, employee) no usuário
- [x] Apenas admin acessa este recurso
- [x] Testes passando

### US-003: Painel Gestor — Cadastro de Colaboradores
**Descrição:** Como gestor, quero cadastrar colaboradores com suas informações (nome, matrícula, lotação, cargo, organização, turno padrão) para que possam usar o sistema.

**Critérios de Aceite:**
- [x] Resource Filament para Employee com CRUD
- [x] Campos: nome, matrícula, lotação, cargo/função, organização, turno padrão (manhã/tarde)
- [x] Ao criar colaborador, cria automaticamente um User vinculado com role `employee`
- [x] Validação de matrícula única
- [x] Gestor e Admin podem acessar
- [x] Testes passando

### US-004: Painel Gestor — Gerenciamento de Feriados, Pontos Facultativos e Dispensas
**Descrição:** Como gestor, quero cadastrar feriados, pontos facultativos e dispensas parciais para que o sistema saiba quais dias não são úteis e quais colaboradores estão liberados.

**Critérios de Aceite:**
- [ ] Resource Filament para Holiday com CRUD
- [ ] Campos: data, nome, tipo (feriado/ponto facultativo/dispensa parcial), recorrente (sim/não)
- [ ] Campo "Abrangência": todos os colaboradores ou apenas selecionados
- [ ] Quando abrangência = parcial: formulário para selecionar colaboradores liberados e informar motivo da dispensa para cada um
- [ ] Colaboradores dispensados parcialmente NÃO recebem "FALTA" naquele dia
- [ ] Listagem com filtro por mês/ano e tipo
- [ ] Gestor e Admin podem acessar
- [ ] Testes passando

### US-005: Painel Colaborador — Marcar Ponto
**Descrição:** Como colaborador, quero marcar meu ponto de entrada e saída com um clique para registrar minha frequência.

**Critérios de Aceite:**
- [ ] Página/widget no dashboard do colaborador com botão "Marcar Ponto"
- [ ] Sistema identifica automaticamente qual campo preencher (entrada manhã, saída manhã, entrada tarde, saída tarde) com base no turno do colaborador e horários já registrados no dia
- [ ] Horário registrado automaticamente (hora atual do servidor)
- [ ] Rúbrica preenchida automaticamente com nome do colaborador
- [ ] Se o colaborador está em turno diferente do padrão (troca pontual), ele pode selecionar o turno antes de marcar
- [ ] Confirmação visual após marcação
- [ ] Testes passando

### US-006: Painel Colaborador — Visualizar Meus Pontos
**Descrição:** Como colaborador, quero ver meus pontos marcados no mês para acompanhar minha frequência.

**Critérios de Aceite:**
- [ ] Tabela/calendário mostrando todos os dias do mês selecionado
- [ ] Exibe horários de entrada/saída de cada turno marcado
- [ ] Dias úteis sem marcação aparecem destacados visualmente (cor vermelha ou ícone de alerta)
- [ ] Dias com dispensa parcial (colaborador liberado) aparecem com indicação visual diferente (ex: ícone ou cor azul) e motivo visível
- [ ] Feriados e pontos facultativos identificados visualmente
- [ ] Filtro por mês/ano
- [ ] Finais de semana diferenciados visualmente
- [ ] Testes passando

### US-007: Exportação — Folha Individual de Frequência (Excel)
**Descrição:** Como gestor, quero exportar a folha de frequência individual de um colaborador em Excel no formato exato do template existente.

**Critérios de Aceite:**
- [ ] Botão "Exportar Excel" na listagem de colaboradores ou na página do colaborador
- [ ] Seleção de mês/ano para exportação
- [ ] Formato idêntico ao template `FREQUÊNCIA - MODELO.xlsx`:
  - Cabeçalho com dados da instituição (ESTADO DO MARANHAO, AGED-MA)
  - Dados do colaborador (matrícula, nome, período, cargo, lotação, organização)
  - Tabela com colunas: DIA | MANHÃ (ENTRADA: HORA/RUBRICA, SAÍDA: HORA/RUBRICA) | TARDE (ENTRADA: HORA/RUBRICA, SAÍDA: HORA/RUBRICA)
  - 31 linhas de dias
  - Seção OBSERVAÇÃO com feriados e pontos facultativos do mês
  - Campos de assinatura (Responsável pela frequência / Chefe Imediato)
- [ ] Dias sem marcação em dia útil: campos preenchidos com "FALTA"
- [ ] Dispensas parciais: colaborador liberado NÃO aparece como "FALTA"; linha fica em branco e o motivo vai para a seção OBSERVAÇÃO (ex: "Dia 15 - Dispensa parcial: Convocação para evento externo")
- [ ] Feriados/pontos facultativos gerais: linha em branco, listados na seção OBSERVAÇÃO
- [ ] Finais de semana: linhas em branco
- [ ] Testes passando

### US-008: Exportação — Folha Individual de Frequência (PDF)
**Descrição:** Como gestor, quero exportar a folha de frequência em PDF para impressão.

**Critérios de Aceite:**
- [ ] Botão "Exportar PDF" junto ao botão de Excel
- [ ] Layout idêntico ao Excel, formatado para impressão A4
- [ ] Mesmo conteúdo e regras do US-007
- [ ] Testes passando

### US-009: Exportação em Lote
**Descrição:** Como gestor, quero exportar todas as folhas de frequência de todos os colaboradores de uma vez para facilitar a impressão mensal.

**Critérios de Aceite:**
- [ ] Action na listagem de colaboradores para exportar todos
- [ ] Seleção de mês/ano
- [ ] Gera arquivo Excel com uma aba por colaborador (igual template original) ou PDF com uma página por colaborador
- [ ] Testes passando

### US-010: Autorização e Políticas de Acesso
**Descrição:** Como admin, quero que cada perfil acesse apenas o que lhe é permitido.

**Critérios de Aceite:**
- [ ] **Admin:** acesso total (gerenciar usuários, colaboradores, feriados, ver todos os pontos, exportar)
- [ ] **Gestor (Manager):** cadastrar colaboradores, gerenciar feriados, ver pontos de todos, exportar
- [ ] **Colaborador (Employee):** marcar próprio ponto, ver próprios pontos apenas
- [ ] Policies Laravel para cada model
- [ ] Testes de autorização passando

## Requisitos Funcionais

- FR-1: O sistema deve ter três perfis de acesso: Admin, Gestor e Colaborador
- FR-2: Admin pode gerenciar usuários, colaboradores, feriados e visualizar/exportar tudo
- FR-3: Gestor pode cadastrar colaboradores com matrícula, nome, lotação, cargo, organização e turno padrão
- FR-4: Gestor pode cadastrar feriados, pontos facultativos e dispensas parciais por data, com opção de recorrência anual
- FR-4.1: Dispensas parciais permitem selecionar quais colaboradores são liberados e registrar o motivo individualmente
- FR-4.2: Colaboradores com dispensa parcial não recebem "FALTA"; o motivo aparece no campo OBSERVAÇÃO da exportação
- FR-5: Colaborador pode marcar ponto com um clique; o sistema registra hora e rúbrica automaticamente
- FR-6: O sistema deve identificar automaticamente qual campo preencher (entrada/saída, manhã/tarde) ao marcar ponto
- FR-7: Colaborador pode trocar pontualmente seu turno em um dia específico (ex: ir de manhã em vez de tarde)
- FR-8: Colaborador pode visualizar seus pontos do mês com destaque para dias sem marcação
- FR-9: Dias úteis sem marcação devem ser identificados como "FALTA" na exportação
- FR-10: Exportação em Excel deve seguir o formato exato do template `FREQUÊNCIA - MODELO.xlsx`
- FR-11: Exportação em PDF deve ter o mesmo layout, formatado para impressão A4
- FR-12: Gestor pode exportar folhas individuais ou em lote (todos os colaboradores)
- FR-13: Turno manhã: 08:00–14:00. Turno tarde: 13:00–19:00. Horários fixos por enquanto.

## Fora do Escopo (Non-Goals)

- Geolocalização ou controle de IP na marcação de ponto
- Horas extras ou banco de horas
- Notificações por email/push
- Aprovação de ponto pelo gestor
- Integração com sistemas externos de folha de pagamento
- Relatórios analíticos ou dashboards gerenciais
- Edição manual de pontos já marcados (v1)
- Turno noturno ou turnos customizados além de manhã/tarde

## Considerações Técnicas

- **Stack:** Laravel 12 + Filament v5 + Livewire 4 + SQLite
- **Exportação Excel:** Utilizar pacote como `maatwebsite/excel` ou `openspout/openspout` para gerar o arquivo no formato do template
- **Exportação PDF:** Utilizar `barryvdh/laravel-dompdf` ou similar
- **Painéis Filament:** Considerar um único painel com recursos condicionados por role, ou painéis separados (admin/employee)
- **Autenticação:** Usar o sistema de auth padrão do Laravel já configurado no Filament
- **Dados do cabeçalho da exportação:** Campos como "ESTADO DO MARANHAO", "AGED-MA", etc. devem ser configuráveis via settings ou config

## Métricas de Sucesso

- Colaborador marca ponto em no máximo 2 cliques
- Exportação gera arquivo idêntico ao template existente
- Gestor consegue exportar folhas de todos os colaboradores em menos de 1 minuto
- Zero trabalho manual para o gestor além de definir feriados e imprimir

## Questões em Aberto

- Os dados do cabeçalho (ESTADO DO MARANHAO, AGED-MA, etc.) são fixos ou devem ser configuráveis por organização?
- O colaborador pode ver pontos de meses anteriores ou apenas o mês atual?
- Existe alguma regra de tolerância de horário (ex: chegou 08:05, conta como 08:00)?
- O gestor precisa poder editar/corrigir pontos de um colaborador?
- A observação no rodapé da folha deve listar automaticamente os feriados do mês?
- Um colaborador pode ter múltiplas dispensas parciais no mesmo mês?
