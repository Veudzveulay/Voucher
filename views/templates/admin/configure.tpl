{*
* 2007-2023 PrestaShop
*
* NOTICE OF LICENSE
*
* This source file is subject to the Academic Free License (AFL 3.0)
* that is bundled with this package in the file LICENSE.txt.
* It is also available through the world-wide-web at this URL:
* http://opensource.org/licenses/afl-3.0.php
* If you did not receive a copy of the license and are unable to
* obtain it through the world-wide-web, please send an email
* to license@prestashop.com so we can send you a copy immediately.
*
* DISCLAIMER
*
* Do not edit or add to this file if you wish to upgrade PrestaShop to newer
* versions in the future. If you wish to customize PrestaShop for your
* needs please refer to http://www.prestashop.com for more information.
*
*  @author    PrestaShop SA <contact@prestashop.com>
*  @copyright 2007-2023 PrestaShop SA
*  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
*  International Registered Trademark & Property of PrestaShop SA
*}

<div style="margin-bottom: 140px!important;">
<a href="https://www.arpa3.fr/" target="_blank"><span id="entete"></span></a>
</div>
<div class="panel">
	<form class="form" action="{Tools::safeOutput($smarty.server.REQUEST_URI)}" method="post" style="width:100%;display:inline-block">
		<div class="panel-heading"><h2>Gestion Promotions</h2></div>

		<div class="row">
			<div class="col-md-12">
				Pour [palier quantité] [produit cible] acheté, [quantité à offrir] [produit offert] sont offerts du [début promotion] à [fin promotion]
			</div>
		</div>

		<div class="panel-body">
			<div class="row">
				<div class="col-md-1">
					<label for="taux" class="form-control-label">Palier quantité</label>
					<input type="number" id="taux" class="form-control" name="taux" min="1" step="1" value="1" required>
				</div>
				<div class="col-md-2">
					<label class="form-control-label">Choix du produit cible</label>
					<select name="chooseproduct" class="form-control" required>
						<option value="">Veuillez choisir un produit</option>
						{foreach from=$productlist key=key item=name}
							<option value="{$key}">{$key} - {$name}</option>
						{/foreach}
					</select>
				</div>

				<div class="col-md-1">
					<label for="product-gift-quantity" class="form-control-label">Quantité à offrir</label>
					<input type="number" id="product-gift-quantity" class="form-control" name="product-gift-quantity" min="1" step="1" value="1" required>
				</div>

				<div class="col-md-2">
					<label class="form-control-label">Choix du produit offert</label>
					<select name="choose-product-gift" class="form-control" required>
						<option value="">Veuillez choisir un produit à offrir</option>
						{foreach from=$productlist key=key item=name}
							<option value="{$key}">{$key} - {$name}</option>
						{/foreach}
					</select>
				</div>

				<div class="col-md-2">
					<label for="date_from" class="form-control-label">Début promotion</label>
					<input type="date" id="date_from" class="form-control" name="date_from" required>
				</div>
				<div class="col-md-2">
					<label for="date_to" class="form-control-label">Fin promotion</label>
					<input type="date" id="date_to" class="form-control" name="date_to">
				</div>
			</div>
		</div>
		<div class="panel-footer">
			<button type="submit" name="create" class="btn btn-primary uppercase mt-3 pull-right" style="border-radius: unset">Créer</button>
		</div>
	</form>
</div>
<div class="panel">
	<h3><i class="icon icon-credit-card"></i> {l s='arpa3voucher' mod='arpa3voucher'}</h3>
	<p>
		{if isset($voucherList)}
		<table class="table">
		<thead class="thead-dark">
			<tr>
				<th class="boldth">Palier quantité</th>
				<th class="boldth">Product cible</th>
				<th class="boldth">Quantité à offrir</th>
				<th class="boldth">Product offert</th>
				<th class="boldth">Date debut</th>
				<th class="boldth">Date fin</th>
				<th class="boldth">Enregistrer</th>
			</tr>
			</thead>
			{foreach from=$voucherList item=voucher}
			<tr>
				<form class="form" action="{Tools::safeOutput($smarty.server.REQUEST_URI)}" method="post">
						<input type="hidden" name="id_arpa3voucher" value="{$voucher.id}" />
					<td>
						<input type="number" id="taux" class="form-control" name="taux" min="1" step="1" value="{$voucher.taux}" required>
					</td>
					<td>
						({$voucher.id_product_cible}) <label>{ProductCore::getProductName($voucher.id_product_cible)}</label>
					</td>
					<td>
						<input type="number" id="product-gift-quantity" class="form-control" name="product-gift-quantity" min="1" step="1" value="{$voucher.product_gift_quantity}" required>
					</td>
					<td>
						<input type="hidden" name="giftid" value="{$voucher.id_product_gift_virtual}">
						({$voucher.id_product_gift_virtual}) <label>{ProductCore::getProductName($voucher.id_product_gift_virtual)}</label>
					</td>
					<td>
						<input type="date" id="date_from" class="form-control" name="date_from" value="{$voucher.date_from}" required>
					</td>
					<td {if strtotime($voucher.date_to) < strtotime('today')}class="date-error"{/if}>
						<input type="date" id="date_to" class="form-control {if strtotime($voucher.date_to) < strtotime('today')}date-error"{/if}" name="date_to" value="{$voucher.date_to}">
					</td>
					<td>
						<input type="submit" class="btn btn-primary btn-xs" style="border-radius: unset" name="update" value="Enregistrer" />
						<input type="submit" class="btn btn-danger btn-xs" name="delete" style="border-radius: unset" value="Supprimer" onclick='return confirm("Etes-vous certain ? Cette action est irréversible.")'>
					</td>
				</form>
			</tr>
			{/foreach}
		</table>
		{else}
			Pas de réductions enregitrées.
		{/if}
	</p>
</div>
