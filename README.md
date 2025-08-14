# BudgetControl Application Tests

Libreria per la gestione ed esecuzione di test PHP nell'ambito del progetto BudgetControl.

## Installazione

```bash
composer require --dev budgetcontrol/application-tests
```

## Utilizzo dell'eseguibile Seeds

Dopo l'installazione, puoi utilizzare l'eseguibile per gestire i seed del tuo progetto:

### Comandi base

```bash
# Esegui tutti i seed nella directory resources/seeds
vendor/bin/budgetcontrol-seeds

# Esegui con output verbose
vendor/bin/budgetcontrol-seeds --verbose

# Elenca tutti i seed disponibili senza eseguirli
vendor/bin/budgetcontrol-seeds --list

# Esegui un seed specifico
vendor/bin/budgetcontrol-seeds --seed=UserSeed

# Usa una directory personalizzata per i seed
vendor/bin/budgetcontrol-seeds --path=database/seeds

# Esegui in modalità silenziosa
vendor/bin/budgetcontrol-seeds --quiet
```

### Opzioni disponibili

- `--verbose, -v`: Abilita output dettagliato
- `--quiet, -q`: Disabilita tutto l'output
- `--help, -h`: Mostra il messaggio di aiuto
- `--list, -l`: Elenca tutti i seed trovati senza eseguirli
- `--path=<path>`: Percorso personalizzato alla directory dei seed (default: resources/seeds)
- `--seed=<name>`: Esegui solo un seed specifico per nome

## Struttura dei seed

I seed devono implementare l'interfaccia `SeedInterface`:

```php
<?php

use Budgetcontrol\ApplicationTests\Seeds\SeedInterface;

class MySeed implements SeedInterface
{
    public function run(): void
    {
        // Logica del seed
    }
    
    public function getDependencies(): array
    {
        return []; // Altri seed da eseguire prima
    }
    
    public function getName(): string
    {
        return 'MySeed';
    }
    
    public function shouldRun(): bool
    {
        return true; // Condizioni per l'esecuzione
    }
    
    public function getDescription(): string
    {
        return 'Descrizione del seed';
    }
}
```

## Directory dei seed

Di default, l'eseguibile cerca i seed in queste directory (in ordine):

1. `./resources/seeds`
2. `./database/seeds`
3. `../resources/seeds`
4. `../database/seeds`
5. `{project_root}/resources/seeds` (se eseguito da vendor)
6. `{project_root}/database/seeds` (se eseguito da vendor)

## Integrazione con Phinx

Il package include anche integrazione con Phinx per l'esecuzione automatica dei seed:

```bash
# Dopo aver configurato phinx.php
vendor/bin/phinx seed:run
```

## Esempi

```bash
# Sviluppo con output dettagliato
vendor/bin/budgetcontrol-seeds --verbose

# Produzione silenziosa
vendor/bin/budgetcontrol-seeds --quiet

# Test di un seed specifico
vendor/bin/budgetcontrol-seeds --seed=TestDataSeed --verbose

# Verifica seed disponibili
vendor/bin/budgetcontrol-seeds --list
```

## Gestione degli errori

L'eseguibile gestisce automaticamente:

- Dipendenze circolari tra seed
- Seed mancanti o malformati
- Errori durante l'esecuzione
- Logging dettagliato con timestamp e livelli

## File template

Il package crea automaticamente:

- `resources/seeds/SeedExample.php`: Esempio di seed
- `phinx.php`: Configurazione Phinx pre-configurata
