<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('qualification_batches', function (Blueprint $table) {
            $table->id();
            $table->string('batch_no', 50)->unique()->comment('批次编号');
            $table->string('batch_name', 200)->comment('批次名称');
            $table->string('source', 50)->default('manual')->comment('数据来源:manual民政接口导入');
            $table->date('review_date')->comment('复核日期');
            $table->date('valid_from')->nullable()->comment('有效期起');
            $table->date('valid_to')->nullable()->comment('有效期止');
            $table->integer('total_count')->default(0)->comment('总记录数');
            $table->integer('pass_count')->default(0)->comment('通过数');
            $table->integer('fail_count')->default(0)->comment('不通过数');
            $table->integer('pending_count')->default(0)->comment('待复核数');
            $table->tinyInteger('status')->default(1)->comment('状态:1草稿2已发布3已完成');
            $table->text('remark')->nullable()->comment('备注');
            $table->string('import_file', 500)->nullable()->comment('导入文件路径');
            $table->foreignId('created_by')->nullable()->constrained('users');
            $table->foreignId('updated_by')->nullable()->constrained('users');
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('qualification_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('batch_id')->constrained('qualification_batches')->onDelete('cascade');
            $table->foreignId('resident_id')->nullable()->constrained();
            $table->string('id_card', 18)->comment('身份证号');
            $table->string('name', 50)->comment('姓名');
            $table->string('phone', 20)->nullable()->comment('联系电话');
            $table->tinyInteger('result')->comment('复核结果:1通过2不通过3待复核');
            $table->string('reason', 500)->nullable()->comment('不通过原因');
            $table->string('review_type', 50)->nullable()->comment('复核类型:收入资产住房');
            $table->decimal('income_amount', 15, 2)->nullable()->comment('收入金额');
            $table->decimal('asset_amount', 15, 2)->nullable()->comment('资产金额');
            $table->decimal('house_area', 10, 2)->nullable()->comment('住房面积');
            $table->tinyInteger('family_member_count')->nullable()->comment('家庭成员数');
            $table->text('remark')->nullable()->comment('备注');
            $table->boolean('affects_renewal')->default(true)->comment('是否影响续租');
            $table->foreignId('reviewed_by')->nullable()->constrained('users');
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();
            $table->index(['batch_id', 'result']);
            $table->index(['id_card', 'result']);
        });

        Schema::create('system_configs', function (Blueprint $table) {
            $table->id();
            $table->string('config_key', 100)->unique();
            $table->text('config_value');
            $table->string('config_type', 50)->default('string');
            $table->string('description', 200)->nullable();
            $table->boolean('is_public')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('system_configs');
        Schema::dropIfExists('qualification_records');
        Schema::dropIfExists('qualification_batches');
    }
};
