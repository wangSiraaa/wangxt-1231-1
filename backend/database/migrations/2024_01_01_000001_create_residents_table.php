<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('residents', function (Blueprint $table) {
            $table->id();
            $table->string('id_card', 18)->unique()->comment('身份证号');
            $table->string('name', 50)->comment('姓名');
            $table->string('phone', 20)->nullable()->comment('联系电话');
            $table->tinyInteger('gender')->comment('性别:1男2女');
            $table->date('birth_date')->nullable()->comment('出生日期');
            $table->string('address', 200)->nullable()->comment('户籍地址');
            $table->string('building', 20)->nullable()->comment('楼栋');
            $table->string('unit', 20)->nullable()->comment('单元');
            $table->string('room', 20)->nullable()->comment('房间号');
            $table->decimal('house_area', 10, 2)->nullable()->comment('房屋面积');
            $table->tinyInteger('status')->default(1)->comment('状态:1正常2迁出3死亡');
            $table->text('remark')->nullable()->comment('备注');
            $table->foreignId('created_by')->nullable()->constrained('users');
            $table->foreignId('updated_by')->nullable()->constrained('users');
            $table->timestamps();
            $table->softDeletes();
            $table->index(['id_card', 'status']);
            $table->index(['building', 'unit', 'room']);
        });

        Schema::create('resident_family_members', function (Blueprint $table) {
            $table->id();
            $table->foreignId('resident_id')->constrained()->onDelete('cascade');
            $table->string('id_card', 18)->comment('身份证号');
            $table->string('name', 50)->comment('姓名');
            $table->string('relation', 20)->comment('与户主关系');
            $table->tinyInteger('gender')->comment('性别:1男2女');
            $table->date('birth_date')->nullable()->comment('出生日期');
            $table->string('phone', 20)->nullable()->comment('联系电话');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('resident_family_members');
        Schema::dropIfExists('residents');
    }
};
