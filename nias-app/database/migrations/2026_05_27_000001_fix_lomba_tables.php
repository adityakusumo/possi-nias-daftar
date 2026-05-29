<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ── 1. MstPeserta: PRIMARY KEY AUTO_INCREMENT on IDPESERTA ──
        DB::statement('ALTER TABLE MstPeserta MODIFY COLUMN IDPESERTA int(11) NOT NULL');
        DB::statement('ALTER TABLE MstPeserta ADD PRIMARY KEY (IDPESERTA)');
        DB::statement('ALTER TABLE MstPeserta MODIFY COLUMN IDPESERTA int(11) NOT NULL AUTO_INCREMENT');

        // ── 2. Atlet: PRIMARY KEY AUTO_INCREMENT on IDATLET ──────────
        DB::statement('ALTER TABLE Atlet MODIFY COLUMN IDATLET int(11) NOT NULL');
        DB::statement('ALTER TABLE Atlet ADD PRIMARY KEY (IDATLET)');
        DB::statement('ALTER TABLE Atlet MODIFY COLUMN IDATLET int(11) NOT NULL AUTO_INCREMENT');

        // ── 3. A3: PRIMARY KEY AUTO_INCREMENT on IDA3P ──────────────
        DB::statement('ALTER TABLE A3 MODIFY COLUMN IDA3P int(11) NOT NULL');
        DB::statement('ALTER TABLE A3 ADD PRIMARY KEY (IDA3P)');
        DB::statement('ALTER TABLE A3 MODIFY COLUMN IDA3P int(11) NOT NULL AUTO_INCREMENT');

        // ── 4. rKwtDaftarDeposit: PRIMARY KEY AUTO_INCREMENT on IDKWTTOT ──
        DB::statement('ALTER TABLE rKwtDaftarDeposit MODIFY COLUMN IDKWTTOT int(11) NOT NULL');
        DB::statement('ALTER TABLE rKwtDaftarDeposit ADD PRIMARY KEY (IDKWTTOT)');
        DB::statement('ALTER TABLE rKwtDaftarDeposit MODIFY COLUMN IDKWTTOT int(11) NOT NULL AUTO_INCREMENT');

        // ── 5. PesertaEmail: add IDPESERTAEMAIL INT AUTO_INCREMENT PK ───
        DB::statement('ALTER TABLE PesertaEmail ADD COLUMN IDPESERTAEMAIL INT NOT NULL AUTO_INCREMENT FIRST, ADD PRIMARY KEY (IDPESERTAEMAIL)');

        // ── 6. Create NOLOMBAAKTIF table ────────────────────────────
        Schema::create('NOLOMBAAKTIF', function (Blueprint $table) {
            $table->integer('ID')->autoIncrement()->primary();
            $table->string('GAYA', 50);
            $table->string('AKTIF', 1)->default('A');
            $table->string('KATEGORI', 20)->default('Perorangan');
            $table->timestamps();
        });

        // ── 7. Add user_id & lomba_user_id to MstPeserta ────────────
        Schema::table('MstPeserta', function (Blueprint $table) {
            $table->unsignedBigInteger('user_id')->nullable()->after('email');
            $table->unsignedBigInteger('lomba_user_id')->nullable()->after('user_id');
        });
    }

    public function down(): void
    {
        // Reverse order of up()

        // 7. Remove columns from MstPeserta
        Schema::table('MstPeserta', function (Blueprint $table) {
            $table->dropColumn(['user_id', 'lomba_user_id']);
        });

        // 6. Drop NOLOMBAAKTIF table
        Schema::dropIfExists('NOLOMBAAKTIF');

        // 5. Remove PK and column from PesertaEmail
        DB::statement('ALTER TABLE PesertaEmail DROP PRIMARY KEY');
        DB::statement('ALTER TABLE PesertaEmail DROP COLUMN IDPESERTAEMAIL');

        // 4. Revert rKwtDaftarDeposit
        DB::statement('ALTER TABLE rKwtDaftarDeposit MODIFY COLUMN IDKWTTOT int(11) NOT NULL');
        DB::statement('ALTER TABLE rKwtDaftarDeposit DROP PRIMARY KEY');
        DB::statement('ALTER TABLE rKwtDaftarDeposit MODIFY COLUMN IDKWTTOT int(11) DEFAULT NULL');

        // 3. Revert A3
        DB::statement('ALTER TABLE A3 MODIFY COLUMN IDA3P int(11) NOT NULL');
        DB::statement('ALTER TABLE A3 DROP PRIMARY KEY');
        DB::statement('ALTER TABLE A3 MODIFY COLUMN IDA3P int(11) DEFAULT NULL');

        // 2. Revert Atlet
        DB::statement('ALTER TABLE Atlet MODIFY COLUMN IDATLET int(11) NOT NULL');
        DB::statement('ALTER TABLE Atlet DROP PRIMARY KEY');
        DB::statement('ALTER TABLE Atlet MODIFY COLUMN IDATLET int(11) DEFAULT NULL');

        // 1. Revert MstPeserta
        DB::statement('ALTER TABLE MstPeserta MODIFY COLUMN IDPESERTA int(11) NOT NULL');
        DB::statement('ALTER TABLE MstPeserta DROP PRIMARY KEY');
        DB::statement('ALTER TABLE MstPeserta MODIFY COLUMN IDPESERTA int(11) DEFAULT NULL');
    }
};
