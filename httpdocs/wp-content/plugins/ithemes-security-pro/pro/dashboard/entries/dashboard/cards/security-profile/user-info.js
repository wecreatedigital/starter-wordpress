/**
 * External dependencies
 */
import classnames from 'classnames';

/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import { dateI18n } from '@wordpress/date';

/**
 * Internal dependencies
 */
import { getPasswordStrength, getTwoFactor } from './utils';

export default function UserInfo( { user } ) {
	return (
		<section className="itsec-card-security-profile__user-info">
			<table>
				<tbody>
					<tr>
						<th>{ __( 'Role', 'ithemes-security-pro' ) }</th>
						<td>{ user.role }</td>
					</tr>
					<tr>
						<th>{ __( 'Password Strength', 'ithemes-security-pro' ) }</th>
						<td>
							<span className={ classnames(
								'itsec-card-security-profile__password-strength',
								`itsec-card-security-profile__password-strength--${ getPasswordStrength( user.password_strength )[ 0 ] }`
							) }>
								{ getPasswordStrength( user.password_strength )[ 1 ] }
							</span>
						</td>
					</tr>
					{ user.password_last_changed && (
						<tr>
							<th>{ __( 'Password Age', 'ithemes-security-pro' ) }</th>
							<td>
								<span title={ dateI18n( 'M d, Y g:s A', user.password_last_changed.time ) }>
									{ user.password_last_changed.diff }
								</span>
							</td>
						</tr>
					) }
					<tr>
						<th>{ __( 'Two-Factor', 'ithemes-security-pro' ) }</th>
						<td>{ getTwoFactor( user.two_factor )[ 1 ] }</td>
					</tr>
					{ user.last_active && (
						<tr>
							<th>{ __( 'Last Seen', 'ithemes-security-pro' ) }</th>
							<td>
								<span title={ dateI18n( 'M d, Y g:s A', user.last_active.time ) }>
									{ user.last_active.diff }
								</span>
							</td>
						</tr>
					) }
				</tbody>
			</table>
		</section>
	);
}
