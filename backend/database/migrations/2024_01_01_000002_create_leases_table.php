<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('leases', function (Blueprint $table) {
            $table->id();
            $table->string('lease_no', 50)->unique()->comment('租约编号');
            $table->foreignId('resident_id')->constrained();
            $table->date('start_date')->comment('起租日期');
            $table->date('end_date')->comment('到期日期');
            $table->decimal('monthly_rent', 12, 2)->comment('月租金');
            $table->decimal('deposit', 12, 2)->nullable()->comment('押金');
            $table->decimal('area', 10, 2)->comment('租赁面积');
            $table->tinyInteger('lease_type')->default(1)->comment('租约类型:1公租房2廉租房');
            $table->tinyInteger('status')->default(1)->comment('状态:1生效2到期3终止');
            $table->tinyInteger('renewal_status')->default(0)->comment('续租状态:0不可续租1可续租2已申请3已审批');
            $table->text('remark')->nullable()->comment('备注');
            $table->foreignId('created_by')->nullable()->constrained('users');
            $table->foreignId('updated_by')->nullable()->constrained('users');
            $table->timestamps();
            $table->softDeletes();
            $table->index(['resident_id', 'status']);
            $table->index(['end_date', 'status']);
        });

        Schema::create('lease_renewal_applications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lease_id')->constrained();
            $table->foreignId('resident_id')->constrained();
            $table->date('apply_date')->comment('申请日期');
            $table->date('new_start_date')->nullable()->comment('新租约开始日期');
            $table->date('new_end_date')->nullable()->comment('新租约到期日期');
            $table->decimal('new_monthly_rent', 12, 2)->nullable()->comment('新月租');
            $table->tinyInteger('qualification_result')->nullable()->comment('资格复核结果:1通过2不通过');
            $table->tinyInteger('status')->default(1)->comment('状态:1待审核2通过3驳回');
            $table->text('audit_opinion')->nullable()->comment('审核意见');
            $table->foreignId('audited_by')->nullable()->constrained('users');
            $table->timestamp('audited_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lease_renewal_applications');
        Schema::dropIfExists('leases');
    }
};
