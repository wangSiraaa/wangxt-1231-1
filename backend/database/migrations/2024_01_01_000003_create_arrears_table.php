<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('arrears', function (Blueprint $table) {
            $table->id();
            $table->string('bill_no', 50)->unique()->comment('账单编号');
            $table->foreignId('resident_id')->constrained();
            $table->foreignId('lease_id')->constrained();
            $table->string('bill_period', 7)->comment('账期:YYYY-MM');
            $table->date('bill_date')->comment('账单日期');
            $table->date('due_date')->comment('缴费截止日期');
            $table->decimal('rent_amount', 12, 2)->default(0)->comment('租金金额');
            $table->decimal('property_fee', 12, 2)->default(0)->comment('物业费');
            $table->decimal('water_fee', 12, 2)->default(0)->comment('水费');
            $table->decimal('electric_fee', 12, 2)->default(0)->comment('电费');
            $table->decimal('gas_fee', 12, 2)->default(0)->comment('燃气费');
            $table->decimal('other_fee', 12, 2)->default(0)->comment('其他费用');
            $table->decimal('total_amount', 12, 2)->comment('应缴总额');
            $table->decimal('paid_amount', 12, 2)->default(0)->comment('已缴金额');
            $table->decimal('unpaid_amount', 12, 2)->comment('欠缴金额');
            $table->decimal('late_fee', 12, 2)->default(0)->comment('滞纳金');
            $table->tinyInteger('status')->default(1)->comment('状态:1未缴2部分缴纳3已缴4逾期');
            $table->date('payment_date')->nullable()->comment('缴费日期');
            $table->string('payment_method', 20)->nullable()->comment('缴费方式');
            $table->text('remark')->nullable()->comment('备注');
            $table->foreignId('created_by')->nullable()->constrained('users');
            $table->foreignId('updated_by')->nullable()->constrained('users');
            $table->timestamps();
            $table->softDeletes();
            $table->index(['resident_id', 'status']);
            $table->index(['lease_id', 'bill_period']);
            $table->index(['status', 'due_date']);
        });

        Schema::create('payment_records', function (Blueprint $table) {
            $table->id();
            $table->string('payment_no', 50)->unique()->comment('缴费流水号');
            $table->foreignId('arrear_id')->constrained();
            $table->foreignId('resident_id')->constrained();
            $table->decimal('amount', 12, 2)->comment('缴费金额');
            $table->string('payment_method', 20)->comment('缴费方式:现金微信支付宝银行转账');
            $table->string('transaction_no', 100)->nullable()->comment('交易流水号');
            $table->date('payment_date')->comment('缴费日期');
            $table->text('remark')->nullable()->comment('备注');
            $table->foreignId('created_by')->nullable()->constrained('users');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_records');
        Schema::dropIfExists('arrears');
    }
};
